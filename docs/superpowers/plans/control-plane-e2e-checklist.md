# Control-Plane End-to-End Smoke Test (Task C1)

Live end-to-end smoke test proving the real agent (inside The Shop) talks to a
running central super-admin app over real HTTP: register → approve → report
→ verify → enforce → restore.

- Branch: `feat/control-plane-foundation`
- Date run: 2026-06-16/17
- The Shop: `codecanyon-34858541-the-shop/install/` (Laravel 9, Docker, `app` on :8000)
- Central app: `control-plane/` (Laravel 10), run via the shop's PHP 8.1 image on the shop's compose network, sharing the shop's MariaDB (separate `control_plane` schema)

## Central URL used

**`http://cp_server:9000`** — the container name resolved correctly from the
shop's `app` container on the shared compose network (no IP fallback needed).

Note: the task's connectivity check hit `GET /login`, which returned `500`
because Breeze's `guest` layout requires built Vite frontend assets
(`public/build/manifest.json`) that were never compiled for this throwaway
central instance — a frontend-asset gap, not a networking/HTTP problem.
Reachability was instead confirmed with `GET /` (home route, no view assets)
→ **200**, and `GET /api/v1/agent/status` (unauthenticated) → **401** (correct
auth-guard behavior). Both prove the shop container reaches `cp_server:9000`
over real HTTP by container name. All subsequent API traffic (register,
report) is JSON-only and unaffected by the Vite issue.

## Results

| Step | Description | Result | Observed values |
|---|---|---|---|
| A | Configure + migrate central app (shared shop MariaDB, DB `control_plane`) | PASS | DB created; `key:generate` OK; migrations: users, password_reset_tokens, failed_jobs, personal_access_tokens, clients, sales_reports — all ran; super-admin user seeded, count=1 |
| B | Start central server (`cp_server`) on shop's docker network | PASS | Server up on `0.0.0.0:9000`; reachable from shop `app` container as `http://cp_server:9000` (home `/` → 200; `/login` → 500 due to missing Vite manifest, unrelated to networking; `/api/v1/agent/status` → 401 as expected unauthenticated) |
| C | Register agent (real HTTP, shop → central) | PASS | `register_status=pending`; token issued (`3AjBWH49...`, non-empty); central side: `1 clients; first status=pending` |
| D | Approve + set commission (central side) | PASS | `approved: active percent 10.00` |
| E | Seed yesterday's paid orders, report, verify | PASS | Seeded 2 demo orders dated 2026-06-15 (120.00 + 80.00, payment_status=paid); `agent:sync-status` → `Status: active`; `agent:report` → `Report accepted.`; central `SalesReport`: `gross=200.00 count=2 currency=USD`; `CommissionCalculator::owed('percent',10,200.00,2)` → `20` (commission_owed=20) |
| F | Enforcement round-trip (maintenance → restore) | PASS | Central set client to `maintenance`; shop `agent:sync-status` → `Status: maintenance`; storefront home → `maintenance_home=503`; central restored client to `active`; shop `agent:sync-status` → `Status: active`; storefront home → `restored_home=200` |

## Demo data notes

- Two throwaway PAID orders were inserted directly into the shop's `orders`
  table, dated 2026-06-15 (yesterday relative to test run), totals 120.00 and
  80.00 (grand_total). These are test fixtures only — not real customer
  orders — and were not removed (no cleanup step was specified; they remain
  in the shop dev DB as agent:report fixture data).
- One throwaway `Client` row (`Demo Store`, demo@example.com) and one
  `SalesReport` row exist in the central `control_plane` DB from this run.
- A super-admin user (`super@admin.test`) was seeded in the central DB for
  this test.

## Store state confirmation

The storefront was deliberately put into maintenance mode as part of step F
to prove central-driven enforcement works, and was **restored to active
immediately afterward**. Final confirmed state: `agent:sync-status` →
`Status: active`, home page → `200`. The live store was not left down.

## Teardown

- `cp_server` container stopped via `docker stop cp_server`; since it was
  started with `--rm`, it was automatically removed. Confirmed gone via
  `docker ps -a --filter name=cp_server` (empty result).

## Conclusion

All steps A–F passed with real HTTP traffic between the shop's agent module
and a live central Laravel app sharing the shop's MariaDB instance over the
shop's docker compose network, using the container DNS name `cp_server`. The
full lifecycle — register, approve, commission config, sales reporting,
commission calculation, and remote enforcement (maintenance + restore) — is
proven working end-to-end.
