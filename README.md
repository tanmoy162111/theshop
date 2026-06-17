# The Shop — Multi-Tenant E-Commerce Platform

A Laravel + Vue storefront (CodeCanyon item **#34858541**) extended into a
**resell-to-many-clients SaaS platform**: each client runs their own
self-hosted store, and a central **super-admin control plane** manages them all
over a REST API — registration/approval, sales reporting, per-client
commission, and soft enforcement.

> **License notice.** This repository contains the source of a **paid
> CodeCanyon product (#34858541)**. The Regular and Extended licenses prohibit
> redistributing the source, so **this repo must stay private.**

---

## Table of contents

- [Architecture](#architecture)
- [Repository layout](#repository-layout)
- [Tech stack](#tech-stack)
- [Local development](#local-development)
- [Building front-end assets](#building-front-end-assets)
- [The control plane (super-admin)](#the-control-plane-super-admin)
- [How a client store connects to the super-admin](#how-a-client-store-connects-to-the-super-admin)
- [Storefront customizations](#storefront-customizations)
- [Deployment](#deployment)
- [Credentials (demo)](#credentials-demo)
- [Common gotchas](#common-gotchas)

---

## Architecture

Two decoupled applications connected **only by outbound HTTPS** from each store:

```
 Client store #1  (Laravel 9 + Vue SPA, own DB) ─┐
 Client store #2  (Laravel 9 + Vue SPA, own DB) ─┼── HTTPS REST (outbound) ──► Control Plane
 Client store #3  (Laravel 9 + Vue SPA, own DB) ─┘                            (Laravel 10 super-admin)
```

- **Storefront** — a Laravel monolith that also serves the Vue 3 / Vuetify SPA.
  The SPA is built by Vite and rendered through a Blade shell; the JSON API
  lives at `/api/v1/...` on the **same origin**.
- **Control plane** — a separate Laravel 10 app (`control-plane/`) where the
  platform owner approves clients, sets commission, views sales, and toggles
  enforcement state.
- **The link** — an in-store **Agent** (`app/Agent/`) that calls the control
  plane. The control plane never reaches into a client server, so stores can
  live behind any host or firewall.

---

## Repository layout

```
.
├── codecanyon-34858541-the-shop/
│   └── install/                     # the storefront app (Laravel 9 + Vue SPA)
│       ├── app/
│       │   ├── Agent/               # in-store agent → talks to the control plane
│       │   ├── Console/Commands/    # AgentReport.php, AgentSyncStatus.php
│       │   ├── Http/Controllers/Api/ # SPA JSON API (SettingController, ...)
│       │   └── Http/Middleware/AgentEnforcement.php
│       ├── resources/js/            # Vue 3 SPA (components, pages, router, store)
│       ├── resources/sass/main.scss # theme tokens + global styles
│       ├── resources/views/         # Blade SPA shell + admin (backend/) views
│       ├── docker-compose.yml       # local dev stack
│       ├── Dockerfile.render        # production image for Render
│       └── docker/render-start.sh   # container entrypoint (DB wait + sql import)
├── control-plane/                   # central super-admin app (Laravel 10)
│   ├── app/Http/Controllers/Api/AgentController.php
│   └── routes/api.php               # /api/v1/agent/{register,report,status}
├── render.yaml                      # Render Blueprint (web + MySQL pserv)
└── docs/
    ├── DEPLOY-RENDER.md             # deployment runbook
    └── superpowers/                 # design specs + execution plans
```

---

## Tech stack

| Layer            | Tech                                                      |
| ---------------- | -------------------------------------------------------- |
| Storefront API   | Laravel **9.19** (PHP 8.1)                                |
| Storefront SPA   | Vue **3.3**, Vuetify 3, Vite 4, Swiper 11, SCSS          |
| Control plane    | Laravel **10.10**, Breeze auth, Vite assets              |
| Database         | MariaDB 10.4 (local) / MySQL 8 (Render)                  |
| Agent transport  | REST over HTTPS, per-client bearer token                 |

---

## Local development

The storefront runs via Docker Compose. **Node is not in the container** —
assets are built on the host and served from the bind mount.

```bash
cd codecanyon-34858541-the-shop/install
docker compose up -d        # theshop-app :8000, theshop-db (MariaDB) :3307
```

Open <http://localhost:8000>. The repo is bind-mounted into the container
(`.:/app`), so PHP changes are live; front-end changes need a rebuild (below).

Useful container commands:

```bash
docker exec theshop-app php artisan view:clear
docker exec theshop-app php artisan cache:clear   # clears cached home payloads
docker exec theshop-app php artisan agent:sync-status
docker exec theshop-app php artisan agent:report
```

---

## Building front-end assets

`public/build` is **git-ignored** and built on the host (Vite). After editing
anything under `resources/js` or `resources/sass`:

```bash
cd codecanyon-34858541-the-shop/install
npm install        # first time only
npm run build      # outputs public/build/** (new content-hashed bundles)
docker exec theshop-app php artisan view:clear
```

Then reload the page — because bundles are content-hashed, a normal refresh
picks up the new build (no cache-busting needed).

---

## The control plane (super-admin)

A standalone Laravel 10 app in `control-plane/`. For local verification it
shares the shop's MariaDB in a separate `control_plane` schema.

```bash
cd control-plane
npm install && npm run build          # required, or /login 500s
docker rm -f cp_server 2>/dev/null
docker run -d --name cp_server --network install_default -p 9000:9000 \
  -v "$PWD/..":/work -w /work/control-plane install-app \
  php artisan serve --host=0.0.0.0 --port=9000
```

Browser: <http://localhost:9000> (the shop agent reaches it in-network as
`http://cp_server:9000`). Super-admin lands on the **Clients** dashboard:
approve registrations, set commission, view reports, set enforcement status.

---

## How a client store connects to the super-admin

**API-based, pull-style, outbound only.** Endpoints live on the control plane
under `/api/v1/agent/`:

| Method & path        | Auth          | Purpose                                                        |
| -------------------- | ------------- | ------------------------------------------------------------- |
| `POST register`      | none (throttled) | Store self-registers (name, email, domain, version). Returns a per-client **token + client_id + `status: pending`**. Super-admin approves → `active`. |
| `POST report`        | bearer token  | Store reports aggregated sales (`gross_sales`, `order_count`, `currency`, period). |
| `GET  status`        | bearer token  | Store pulls current `status`, `commission_type`, `commission_rate`, message. |

**Auth.** Authenticated calls send `Authorization: Bearer <token>` plus an
`X-Agent-Domain` header, validated by the `agent.auth` middleware. Each client
has its own issued token — no shared credentials.

**In-store agent** (`app/Agent/`): `AgentClient` makes the calls;
`AgentConfig`/`AgentSetting` persist token/status/commission in the store's own
`agent_settings` table; `SalesAggregator` rolls up the period.

**Cadence.** The store's scheduler runs `agent:report` daily at 00:30 and
`agent:sync-status` daily (host cron must run `php artisan schedule:run`). Both
are also runnable manually as CLI commands.

**Enforcement (soft, escalating).** The control plane returns a status that
`AgentEnforcement` middleware acts on in the store:

```
active  →  warning  →  locked_admin  →  maintenance
(normal)   (banner)    (admin blocked)   (storefront down)
```

**Fail-open.** If the control plane is unreachable, the agent logs a warning and
the store keeps running on its **last-known status** — a control-plane outage
never takes client stores offline.

Store admins connect/register from the **Platform Connection** page at
`/admin/agent`.

---

## Storefront customizations

Beyond the stock CodeCanyon theme:

- **Bold homepage** — a split hero (cinematic banner + eyebrow + dual CTA, plus
  two promo cards), a trust strip, rounded category cards, and bold product
  cards. Hero copy, CTAs, and promo cards are editable under
  **Admin → Website Setup → Home page → Hero Text & Call To Action**; sensible
  defaults render when fields are blank. (`resources/js/components/home/`)
- **Industry themes** — admin-switchable storefront palettes; accents key off a
  single `--primary` token derived from `base_color` (injected in
  `app.blade.php`).

---

## Deployment

The whole monolith (SPA **and** API) deploys together — see
[`docs/DEPLOY-RENDER.md`](docs/DEPLOY-RENDER.md) for the full runbook.

- **Render Blueprint** (`render.yaml`): a Docker web service
  (`Dockerfile.render` builds assets + PHP deps in-image) plus a MySQL 8 private
  service with a disk. First boot imports `shop.sql` automatically.
- Set `APP_KEY` (`php artisan key:generate --show`) and, after the first deploy,
  `APP_URL` to the live Render URL, then redeploy.

> **Why not Vercel for the SPA?** This is a Laravel monolith — Vercel can't run
> PHP, and hosting only the SPA there breaks login (session + `XSRF-TOKEN`
> cookies are `SameSite=lax` and don't work cross-origin). Deploy the whole app
> on a PHP host (Render). A Vercel-hosted SPA would require decoupling the API
> with CORS + cross-origin auth — a separate project.

---

## Credentials (demo)

| App            | URL (local)             | User                  | Password   |
| -------------- | ----------------------- | --------------------- | ---------- |
| Storefront     | <http://localhost:8000> | —                     | —          |
| Store admin    | `/admin`                | `admin@example.com`   | `password` |
| Control plane  | <http://localhost:9000> | `super@admin.test`    | `password` |

**Change these before any real deployment.**

---

## Common gotchas

- **`public/build` is git-ignored** and rebuilt on host / in-image. Editing
  `resources/js` without `npm run build` shows no change.
- **Seeding `settings` rows in tinker** throws `MassAssignmentException` (the
  `Setting` model has no `$fillable`). Use
  `DB::table('settings')->updateOrInsert(['type'=>$t],['value'=>$v])` then
  `Cache::flush()`. The admin form path is fine.
- **Home API payloads are cached** (`Cache::remember('sliders', ...)`). After
  changing `SettingController`, run `php artisan cache:clear`.
- **`php artisan route:list` errors** in the storefront (a MyFatoorah package
  quirk) — unrelated to serving requests.
- **Scoped Vue CSS doesn't reach child-component DOM.** Render links that need
  scoped styles in-component (`<component :is>`), not through a shared link
  component, or the styles silently don't apply.
