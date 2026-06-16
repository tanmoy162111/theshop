# Multi-Client Control-Plane Foundation — Design Spec

- **Date:** 2026-06-16
- **Status:** Approved (design); pending written-spec review
- **Topic:** Foundation layer for reselling "The Shop" to multiple clients with a central super-admin
- **Related:** `codecanyon-34858541-the-shop/install` (the e-commerce app), memory `theshop-saas-direction`

---

## 1. Context & Problem

"The Shop" is a single-store Laravel 9 e-commerce app. The goal is to resell it as a multi-purpose
product (electronics, supershop, pharmacy, …) to many independent clients and earn a **commission on
each client's product sales**.

Crucially, **each client self-hosts their own copy** of the app on **their own domain and hosting**.
There is no centralized multi-tenant hosting and no shared database. Above all these independent
installs sits a single **central super-admin "control plane"**, operated solely by us, which connects
to each client install **over an API** to monitor them, track each client's total sales, set a
per-client commission rate, and apply soft enforcement when commission goes unpaid.

This spec defines the **foundation**: the integration contract plus the minimal end-to-end slice
(register → approve → report sales → view on a dashboard → set commission/status → client obeys).
Richer dashboards/invoicing and industry themes are separate, later sub-projects (see §11).

## 2. Goals / Non-Goals

### Goals
- A versioned **API contract** between each client install and the central server.
- An **in-app "agent" module** that ships inside The Shop: self-registration, scheduled sales
  reporting, status/commission fetch, and soft-enforcement behavior — **client-initiated only**.
- A **minimal central super-admin app** (separate Laravel application): client registry, approval
  flow, received reports, per-client commission rate, status control, and a basic dashboard.
- **Fail-open resilience**: if the central server is unreachable, a client's store never breaks.

### Non-Goals (deferred)
- Automatic grace-period progression of enforcement tied to real payment collection (→ billing sub-project).
- Invoicing, payment gateways for commission collection, ledgers, payouts (→ billing sub-project).
- Industry themes and the per-install theme picker (→ themes sub-project).
- Centralized multi-tenancy, DB-per-tenant, shared catalog (explicitly rejected).
- Hard license kill-switch / mandatory online activation (explicitly rejected in favor of soft enforcement).

## 3. Architecture Overview

```
        ┌─────────────────────────────────────────────┐
        │        CENTRAL SUPER-ADMIN APP (ours)         │
        │  client registry · reports · commission ·     │
        │  status control · dashboard · approval        │
        └───────────────▲───────────────────────────────┘
                        │  HTTPS (client-initiated, token auth)
        ┌───────────────┴───────────────┬───────────────┐
        │                               │               │
  ┌─────┴──────┐                 ┌──────┴─────┐    ┌─────┴──────┐
  │ Client A    │                │ Client B    │   │ Client C    │
  │ (electronics)│               │ (pharmacy)  │   │ (supershop) │
  │ The Shop +   │               │ The Shop +  │   │ The Shop +  │
  │ agent module │               │ agent module│   │ agent module│
  │ own domain/DB│               │ own domain/DB│  │ own domain/DB│
  └──────────────┘               └─────────────┘   └─────────────┘
```

- **Communication is always initiated by the client** (outbound HTTPS to central). The central
  server never needs inbound access to a client's site, so clients can sit behind any host/firewall.
- Each client install is otherwise an ordinary, isolated single store (own DB, own files, own admin).

## 4. Component: In-App Agent Module (inside The Shop)

Ships as part of The Shop; every client deployment runs it. Responsibilities:

1. **Configuration & registration UI** (in the client's admin): the client enters the **Central URL**
   and their business details (business name, contact email; primary domain auto-detected) and clicks
   **Register**. The agent calls `POST /agent/register`, stores the returned `client_id` + `token`, and
   shows status **Pending approval**.
2. **Scheduled reporting** (`agent:report`, daily via Laravel scheduler): computes the period's sales
   and calls `POST /agent/report`. Idempotent per period (see §6).
3. **Status sync** (`agent:sync-status`, daily and on each admin login): calls `GET /agent/status`,
   caches `commission_rate`, `status`, `message`, and `last_synced_at` in the local settings table.
4. **Soft-enforcement behavior**: middleware reads the cached `status` and acts (see §7).

**Local state** (stored in the existing `settings` table, prefixed `agent_`):
`agent_central_url`, `agent_client_id`, `agent_token`, `agent_status` (default `unregistered`),
`agent_status_message`, `agent_commission_type`, `agent_commission_rate`, `agent_last_report_at`,
`agent_last_synced_at`.

**Reported data is minimal** (see §6) — no customer PII, no order line items leave the client site.

## 5. Component: Central Super-Admin App (new, separate Laravel app)

A brand-new Laravel application, hosted on our own server, with its own database. Not bolted into
The Shop's admin. MVP responsibilities: registry, approval, ingest reports, commission rate, status
control, basic dashboard.

### Data model (central DB)

**`clients`**
| column | type | notes |
|---|---|---|
| id | bigint pk | |
| business_name | string | from registration |
| contact_email | string | from registration |
| primary_domain | string, unique | from registration; used to validate reports |
| status | enum | `pending`, `active`, `warning`, `locked_admin`, `maintenance`, `rejected` |
| commission_type | enum | `percent` (% of gross sales) or `per_order` (flat fee per paid order); default `percent` |
| commission_rate | decimal(20,2) | the value — a percentage when type=`percent`, a flat currency amount per paid order when type=`per_order`; default 0 |
| token | string | stored **hashed**; plaintext shown to client once at registration |
| app_version | string | reported by agent |
| registered_at | timestamp | |
| approved_at | timestamp, nullable | |
| last_report_at | timestamp, nullable | freshness/health |
| last_seen_at | timestamp, nullable | updated on any authenticated call |

**`sales_reports`**
| column | type | notes |
|---|---|---|
| id | bigint pk | |
| client_id | fk | |
| period_start | date | |
| period_end | date | |
| gross_sales | decimal(20,2) | sum of paid orders' grand_total in period |
| order_count | int | paid orders in period |
| currency | string(3) | client's store currency code |
| received_at | timestamp | |
| | unique(client_id, period_start, period_end) | idempotency |

Commission owed is **computed centrally** over the chosen window, based on `commission_type`:
- `percent`: `Σ gross_sales × (commission_rate / 100)`
- `per_order`: `Σ order_count × commission_rate` (flat fee per paid sale)

It is not stored as a ledger in the foundation (that's the billing sub-project).

### Dashboard MVP
- List of clients with status, domain, commission rate, last-seen, lifetime gross sales, computed
  commission owed.
- Per-client detail: report history (table/sparkline), set commission **type** (`percent` / `per_order`)
  and **value**, set status.
- Approval queue for `pending` clients (Approve → `active`; Reject → `rejected`).

## 6. API Contract (v1, client → central)

All requests over HTTPS. `register` is unauthenticated (creates a `pending` record); `report` and
`status` require `Authorization: Bearer <token>`. Central validates the token and that the request's
`domain` matches the registered `primary_domain`.

### `POST /api/v1/agent/register`
Request: `{ business_name, contact_email, domain, app_version }`
Response `201`: `{ client_id, token, status: "pending" }`
- Creates a `clients` row with `status=pending`. Anti-abuse: rate-limited per IP/domain; duplicate
  domain returns the existing pending/active record's status without issuing a new token.

### `POST /api/v1/agent/report`
Request: `{ period_start, period_end, gross_sales, order_count, currency, app_version }`
Response `200`: `{ accepted: true, status, commission_type, commission_rate, message }`
- Upserts a `sales_reports` row keyed by `(client_id, period_start, period_end)` (idempotent retries).
- If client `status=pending`/`rejected`, central returns `accepted:false` with the current status; the
  agent holds and retries later.
- Response **also carries back** the latest `status` + `commission_rate` so reporting doubles as a sync.

### `GET /api/v1/agent/status`
Response `200`: `{ status, commission_type, commission_rate, message, grace_until }`
- Lightweight poll used by `agent:sync-status` and admin-login checks.

**Errors:** standard HTTP codes; `401` invalid token, `403` domain mismatch / rejected, `409` nothing
fatal (idempotent), `429` rate-limited. The agent treats any non-2xx or network failure as "keep last
known status" (fail-open).

## 7. Soft Enforcement — States & Behavior

The central server sets a client's `status`; the agent obeys the **last value it successfully synced**.

| status | client admin | client storefront |
|---|---|---|
| `active` | normal | normal |
| `warning` | dismissible banner ("commission overdue — please settle") | normal |
| `locked_admin` | admin blocked with a notice; storefront keeps selling | normal |
| `maintenance` | admin blocked | storefront shows maintenance page |

- **Fail-open:** unreachable central ⇒ agent keeps last status and retries; it never escalates on its own.
- In the **foundation**, status is set **manually by the super-admin**. Automatic progression
  (`active → warning → locked_admin → maintenance`) driven by unpaid-commission + grace period is part
  of the **billing sub-project**; the `grace_until` field is included now so the contract is forward-compatible.

## 8. Security

- Token auth (Bearer); tokens stored hashed on central, transmitted only over HTTPS.
- Domain binding: authenticated calls must come with a `domain` matching `primary_domain`.
- No inbound connectivity to client sites is ever required.
- Rate limiting on `register` and per-token on `report`/`status`.
- Reported payload contains **no customer PII or order details** — only aggregate totals — which keeps
  pharmacy/health clients clean from a data-protection standpoint.

## 9. What Gets Reported (data source in The Shop)

For a reporting period (default: the previous calendar day):
- `gross_sales` = sum of `orders.grand_total` where `payment_status = 'paid'` and `created_at` in period.
- `order_count` = count of those orders.
- `currency` = store default currency code (settings `DEFAULT_CURRENCY_CODE` / currency setting).
- `app_version` = The Shop version constant.

(Exact treatment of refunds/cancellations is a refinement for the billing sub-project; the foundation
reports paid-order gross totals.)

## 10. Configuration

- **Agent (client side):** `agent_central_url` + credentials live in the `settings` table, set via the
  admin registration screen. The daily `agent:report` and `agent:sync-status` commands are wired into
  the existing Laravel scheduler (clients already run cron for the app).
- **Central:** standard Laravel `.env`; its own DB; deployed on our infrastructure.

## 11. Build Order & Decomposition

1. **This foundation (one sub-project):** API contract + minimal central app (registry, approval,
   ingest, commission rate, status control, basic dashboard) + agent module (registration UI, report
   job, status sync, enforcement middleware) — proven end-to-end.
2. **Billing & richer super-admin (sub-project 3):** invoicing, payment collection for commission,
   automatic grace-period enforcement progression, ledgers, refund/cancellation handling, analytics.
3. **Industry themes (sub-project 4):** ready-made electronics/supershop/pharmacy themes + a per-install
   theme picker in the client admin (independent per install — no cross-client concern).

## 12. Testing Strategy

- **Contract tests** for each endpoint (auth, idempotency, domain binding, error/fail-open paths).
- **Agent tests** against a mock central: registration→pending→approved transition; report idempotency;
  enforcement middleware for each status; fail-open when central is down.
- **Central tests:** approval flow, report upsert/idempotency, commission computation, status setting.
- **End-to-end smoke:** a Shop install registers with a local central, gets approved, reports a day of
  sales, and the dashboard shows correct gross + computed commission; flipping status changes the
  client's behavior on next sync.

## 13. Open Questions (non-blocking; resolve during planning)

- Reporting cadence default (daily vs hourly) — daily assumed; trivially adjustable.
- Whether to show the client their own commission rate and running total in their admin (nice-to-have).
- Backfill: should a newly approved client report historical sales, or only from approval forward?
  (Assume from approval forward for the foundation.)
