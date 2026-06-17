# Deploying The Shop to Render

The repo ships a Render Blueprint (`render.yaml`) plus a production Docker image
(`codecanyon-34858541-the-shop/install/Dockerfile.render`). The app is a Laravel 9
+ Vue SPA; Render has no managed MySQL, so the Blueprint runs MySQL as a private
service with a persistent disk.

## What the Blueprint creates
- **theshop** — the web service (Docker). Builds assets + PHP deps in-image,
  imports `shop.sql` on first boot (gives you the demo data + admin user), then
  serves via PHP's built-in server through `router.php`.
- **theshop-mysql** — MySQL 8 private service with a 1 GB disk for data.
- A 1 GB disk on the web service mounted at `public/uploads` so uploaded images
  survive redeploys.

## One-time manual steps (need your Render account)

1. **Create a Render account** and connect GitHub (authorize the private
   `tanmoy162111/theshop` repo).

2. **Generate an APP_KEY** locally and copy the output:
   ```bash
   cd codecanyon-34858541-the-shop/install
   php artisan key:generate --show     # prints base64:....
   ```
   (Or run `docker compose exec -T app php artisan key:generate --show`.)

3. **New → Blueprint** in the Render dashboard → pick the repo → Render reads
   `render.yaml`. When prompted for the `sync:false` vars on **theshop**, set:
   - `APP_KEY` = the `base64:...` value from step 2.
   - `APP_URL` = leave blank for now (set in step 5).

4. **Apply** the Blueprint. Render builds the image and starts MySQL. First boot
   imports `shop.sql` automatically (watch the web service logs for
   `==> Import complete.`).

5. After the web service goes live, copy its URL (e.g.
   `https://theshop.onrender.com`), set **APP_URL** to it in the service's
   Environment tab, and trigger a redeploy.

6. **Log in** to the admin at `<APP_URL>/admin` — default demo creds are
   `admin@example.com` / `password` (change immediately).

## Notes & caveats
- **PHP built-in server is single-threaded.** Fine for a demo / low traffic. For
  real traffic, switch the image to php-fpm + nginx (or Octane).
- **MySQL port:** the web service uses `DB_PORT=3306`. If the app can't reach the
  DB, check the port Render assigned to `theshop-mysql` and update `DB_PORT`.
  Alternatively use an **external managed MySQL** (e.g. Aiven free tier) and point
  `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` at it — then you can delete the
  `theshop-mysql` service.
- **Sessions/cache use the `file` driver** (no session/cache DB tables exist in
  this app). Logged-in sessions reset on redeploy; acceptable for a demo. Mount a
  disk at `/app/storage` if you need them to persist.
- **MyFatoorah bug:** `php artisan route:list` errors in this app (unrelated to
  deploy); it does not affect serving requests.
- **License:** this is CodeCanyon item #34858541 (paid). Keep the repo **private**.

## Local build check (optional)
```bash
cd codecanyon-34858541-the-shop/install
docker build -f Dockerfile.render -t theshop-render:test .
```
