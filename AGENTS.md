# Agent Workflow

## Project Overview

PPOM (Personalized Product Option Manager) for WooCommerce — a WordPress plugin that lets store owners add custom product fields (text, date, file upload, color, image cropper, etc.) to WooCommerce product pages. Has a free version and a separate Pro add-on (`PPOM_PRO_PATH`).

## Commands

```bash
# Build & dist
npm run build              # i18n pot + grunt addtextdomain
npm run dist               # distribution zip

# Tests (require Docker via wp-env)
npm run env:setup          # start WP Docker
npm run test:unit:php      # PHPUnit
npm run test:e2e           # Playwright (Chromium)
npm run test:e2e:debug     # Playwright UI

# Static analysis
composer run phpstan       # level 8 with baseline
```

Local WP admin creds: `admin` / `password`.

## Code Quality

- **PHP standard**: Themeisle ruleset (`phpcs.xml`). Text domain: `woocommerce-product-addon`.
- **PHPStan**: Level 8 with baseline (`phpstan-baseline.neon`). Scans `src/`, `inc/`, `classes/`, `backend/`, `templates/`, and the main file.
- **Min PHP**: 7.4.
- **PHPDoc `@see`**: Use for cross-file related entry points (esp. `inc/` ↔ `classes/`). File-level docblocks may list two to four canonical functions; avoid long or misleading lists.

## Architecture

See `./architecture.md` for the full map. Non-obvious facts to keep in mind:

- **Hybrid bootstrap**: `woocommerce-product-addon.php` loads Composer autoload, then calls `\PPOM\Plugin::boot()`. Namespaced `src/` code is PSR-4 autoloaded; legacy `inc/`, `classes/`, and `backend/` files are loaded by `PPOM\Core\Bootstrap\LegacyRuntimeLoader`.
- **Two runtime modes**: legacy (`ppom_is_legacy_mode()`, cart fee-based pricing) and modern (default, direct price modification). Rendering, pricing, and conditional logic branch on this.
- **Two conditional logic versions**: `ppom-conditions.js` (old) and `ppom-conditions-v2.js` (new, gated on `ppom_get_conditions_mode() === 'new'`).
- **Free vs Pro**: gated by `defined('PPOM_PRO_PATH')`. License tiers: `LICENSE_PLAN_FREE` (-1), `_1` Essential, `_2` Plus, `_3` VIP.
- **Custom DB table**: `{prefix}_nm_personalized` (constant `PPOM_TABLE_META`); field groups linked to products via `_product_meta_id` post meta.
- **Input types**: each has a class in `classes/inputs/` + a template in `templates/frontend/inputs/`. Template paths filterable via `ppom_input_templates_path`.
- **WC lifecycle**: `woocommerce_add_to_cart_validation` → `woocommerce_add_cart_item_data` → `woocommerce_get_cart_item_from_session` → `woocommerce_get_item_data` → `woocommerce_checkout_create_order_line_item`.
- **WooCommerce compatibility**: HPOS compatibility is declared via `FeaturesUtil::declare_compatibility('custom_order_tables', ..., true)`; Cart/Checkout Blocks compatibility is declared as false.

## WooCommerce Security + Workflow

### Trust boundaries

Treat product page fields, AJAX/REST payloads, cart item data, restored sessions, order item meta, and admin imports as untrusted. Never trust browser-sent prices, fees, labels, field IDs, variation IDs, conditional flags, upload metadata, or Pro gating flags — recompute server-side from saved PPOM/WC configuration. Validate submitted field names/options against the schema attached to the current product. Resolve variation IDs to WC objects and confirm parentage.

### Authorization

- Every state-changing admin/AJAX/REST action requires both a capability check and nonce verification. REST routes need a strict `permission_callback`.
- Never use `is_admin()` as an auth check.
- Scope capabilities narrowly: field-group CRUD, settings, file deletion, import/export, license actions, and diagnostics should not share a blanket permission.

### Data handling

- Sanitize on input (type-appropriate), validate against business rules, escape on output (context-aware `esc_*()`).
- `$wpdb->prepare()` for every dynamic query; pair `LIKE` with `$wpdb->esc_like()`. Never concatenate.
- Prefer WC CRUD APIs over direct post/meta SQL (HPOS-safe).
- Don't persist raw `$_POST`/`$_REQUEST` into cart/session/order meta — store normalized values only.
- Don't leak addon values, upload URLs, or order item meta in logs, notices, REST responses, emails, or templates unless explicitly authorized.

### Pricing + cart integrity

- Pricing is server-authoritative and idempotent — `woocommerce_before_calculate_totals` runs multiple times per request.
- Recalculate addon totals from canonical field definitions during validation, cart restore, and checkout. Don't trust session/browser values carried forward.
- Guard against double-charging on session restore, quantity changes, and overlapping pricing hooks.
- Use current WC objects/state at calc time, not stale cart snapshots, when pricing depends on product type, variation, quantity, tax, currency, or coupons.

### Upload safety

- Allowlist extensions, MIME types, and size; reject executables, double extensions, unexpected archives.
- Generate filenames and paths server-side; keep uploads in the PPOM upload directory. Never trust client-provided path, MIME, or filename.
- Re-check authorization and ownership before serving, deleting, or attaching files.

### Treat as fresh input

Session restore, reorder, and edit-cart flows are untrusted — not trusted historical state.

### Regression checks per change

- Simple + variable products, guest + logged-in checkout, tax modes, coupons/sales, quantity changes, session restore.
- Uploads: invalid MIME/extension, oversized, duplicate filenames, cleanup paths.
- REST/AJAX/admin writes: success, nonce failure, capability failure.
- If enabled in target store: multi-currency, multi-language, HPOS.

## Handling Bugs with `/tdd`

Use `/tdd` for every non-trivial bug fix — red-green-refactor keeps fixes anchored to a failing reproduction.

1. **Red**: write a failing test that reproduces the report (PHPUnit under `tests/` for logic; Playwright under `tests/e2e/` for UI/checkout/admin flows). It must fail for the *same reason* the user reported.
2. **Green**: smallest change that turns the test green. No bundled refactors.
3. **Refactor** with the safety net green throughout.

When writing the failing test, consider the surfaces in the Security section above (lifecycle hooks, pricing idempotency, legacy vs modern mode, Pro gating, REST/AJAX auth paths).

Skip `/tdd` for: typo/copy/doc fixes, dependency bumps with no behavioral change, and hotfixes where reproduction needs production data the test env can't reach — document manual repro in the PR and add the regression test as a follow-up.
