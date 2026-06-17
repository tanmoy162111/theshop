# Store Industry Themes — End-to-End Smoke Results

Date: 2026-06-17
Branch: `feat/control-plane-foundation`
Plan: `docs/superpowers/plans/2026-06-17-store-industry-themes.md`

**Verdict: PASS.** All 11 plan tasks implemented via TDD, 15 theme unit tests green, live MySQL functional smoke and browser smoke both pass. The reversibility guarantee (reset/switch removes only theme-seeded artifacts, never merchant data) holds on the real store DB.

## Automated tests

`docker compose exec -T app php artisan test --filter=Theme` → **15 passed**:
- `ThemeHarnessSmokeTest`, `ThemePresetTest`, `BannerGeneratorTest`
- `ThemeApplierLookTest`, `ThemeApplierResetTest` (incl. merchant-data-safety), `ThemeApplierDemoTest`, `ThemeApplierSwitchTest` (switch / idempotency / fail-safe)
- `ThemeControllerTest`

Independent code review of the `ThemeApplier` engine (Phase D) cross-checked rollback scoping and transaction boundaries against the real `shop.sql` schema (`category_translations` hard-delete vs `product_translations` soft-delete both correct) — **APPROVED**, no critical/important issues.

## Live migration

Blanket `php artisan migrate` is NOT usable on this app (schema ships via SQL dump; unrelated vendor migrations like `pickup_points` collide). Ran the two theme migrations by path instead:

```
php artisan migrate --force \
  --path=database/migrations/2026_06_17_000001_create_theme_applications_table.php \
  --path=database/migrations/2026_06_17_000002_create_theme_application_items_table.php
```
→ both `DONE`. `theme_applications` + `theme_application_items` created on the live `shop` DB.

## Live functional smoke (tinker, real MySQL)

Apply `pharmacy` + demo, then reset:

| Stage | base_color | section 1 title | live categories | live products |
|---|---|---|---|---|
| Before | `#F5A100` | Today's Deals | 5 | 25 |
| Apply pharmacy + demo | `#0D9488` | Medicines | 11 (+6) | 30 (+5) |
| After reset | `#F5A100` ✓ | Today's Deals ✓ | 5 ✓ | 25 ✓ |

After reset: `theme_applications` = 0, `theme_application_items` = 0, banner slot setting restored, section titles removed (were absent before). Merchant baseline fully intact.

## Browser smoke

1. Login `admin@example.com` → admin dashboard. OK.
2. `/admin/theme` — **Blade view renders cleanly** (no Whoops, no MyFatoorah error). 4 preset cards correct: Electronics `#2563EB`, Supershop `#16A34A`, Pharmacy `#0D9488`, Pet Shop `#D97706`, each with header strip / 3 section titles / hex / "Also load sample catalog" / Apply.
3. Apply **Pharmacy + demo** → "Theme applied.", "Active theme: Pharmacy" banner + "demo catalog loaded" badge.
4. Storefront `http://localhost:8000` (hard reload) — `--primary` CSS var = `#0D9488` (teal) applied across buttons/bars; home section titles show "Medicines · Wellness · Personal Care". **Not stale** (applier cleared the `settings` cache).
5. **Reset to default look** → "Theme reset to default look.", active-theme banner gone.

Post-smoke live DB re-verified at baseline (5 categories / 25 products / 0 theme rows / `base_color` `#F5A100`).

## Observations / notes (non-blocking)

- **Home "featured products" widget shows pre-existing store products, not the seeded demo ones.** The storefront home section displays the store's existing products (e.g. ids 21–23 "Vitamin C Serum", "Bamboo Toothbrush Set", "Aroma Diffuser"); the theme changes the section *titles* and primary color there. The seeded demo products are correctly created, tagged, and have their ids written into `home_product_section_N_products`, and are browseable via their categories — but this store's default home widget renders from a different source. Acceptable per spec (scope = look + demo content); revisiting which widget surfaces demo products is a future refinement.
- **Soft-deleted demo rows accumulate.** Rollback soft-deletes seeded products/uploads (`deleted_at`) by design, so repeated apply/reset cycles leave invisible soft-deleted `Demo %` rows (10 after two cycles here). Excluded from the storefront via `deleted_at` + `published_shops_ids()`; slugs carry a random suffix so re-apply never collides. Harmless; a periodic purge could be added later if desired.
- `php artisan route:list` aborts on the pre-existing unrelated MyFatoorah constructor bug; it does not affect page rendering. Theme routes verified via router introspection (`theme.index`/`theme.apply`/`theme.reset`).
