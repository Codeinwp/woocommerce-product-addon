# Agent Workflow

## Project Overview

PPOM (Personalized Product Option Manager) for WooCommerce — a WordPress plugin that lets store owners add custom product fields (text, date, file upload, color, image cropper, etc.) to WooCommerce product pages. Has a free version and a separate Pro add-on (`PPOM_PRO_PATH`).

## Build & Development Commands

```bash
# Install dependencies
composer install
npm install

# Full build (i18n pot + grunt addtextdomain)
npm run build

# Distribution zip
npm run dist
```

## Testing

```bash
# PHPUnit — requires Docker (wp-env)
npm run test:unit:php:setup          # Start WordPress Docker environment
bash ./bin/e2e-after-setup.sh        # Prepare database/test data
npm run test:unit:php                # Run PHPUnit tests

# Run a single PHPUnit test file
wp-env run --env-cwd='wp-content/plugins/woocommerce-product-addon' tests-wordpress vendor/bin/phpunit -c phpunit.xml --filter TestClassName

# E2E tests (Playwright, Chromium only)
npm run test:e2e
npm run test:e2e:debug               # Opens Playwright UI

# Static analysis
composer run phpstan                  # PHPStan level 6
composer run phpstan:generate:baseline
```

## Code Quality

- **PHP standard**: Themeisle ruleset (WordPress-based) via `phpcs.xml`. Text domain: `woocommerce-product-addon`.
- **PHPStan**: Level 6 with a large baseline file (`phpstan-baseline.neon`). Scans `inc/`, `classes/`, `backend/`, `templates/`, and the main plugin file.
- **Min PHP**: 7.2 (composer platform config). CI runs PHPStan on PHP 7.4.

## Architecture

### Entry Point & Bootstrap

`woocommerce-product-addon.php` — defines constants, loads Composer autoload, manually `require_once`s all class/include files (no PSR-4 autoloading for plugin code), then hooks `PPOM()` on `woocommerce_init`.

### Core Classes (all singleton pattern via `get_instance()`)

| Class | File | Role |
|---|---|---|
| `NM_PersonalizedProduct` | `classes/plugin.class.php` | Main plugin — registers all WooCommerce hooks, loads input types |
| `NM_PersonalizedProduct_Admin` | `classes/admin.class.php` | Admin-only — loaded only in `is_admin()` |
| `PPOM_Meta` | `classes/ppom.class.php` | Field group CRUD against custom DB table |
| `PPOM_Form` | `classes/form.class.php` | Frontend form rendering |
| `PPOM_Fields_Meta` | `classes/fields.class.php` | Field type registry and metadata |
| `PPOM_Inputs` | `classes/input.class.php` | Input type manager |

### Input Type System

Each input type has a class in `classes/inputs/` (e.g. `input.text.php`, `input.select.php`) and a corresponding frontend template in `templates/frontend/inputs/`. There are 23+ input types. Template paths are filterable via `ppom_input_templates_path`.

### Include Files (`inc/`)

Procedural utility code organized by concern:
- `functions.php` — general helpers
- `hooks.php` — filter/action callbacks
- `validation.php` — server-side field validation
- `woocommerce.php` — WooCommerce integration hooks (cart, checkout, orders)
- `prices.php` — price calculation logic
- `files.php` — file upload handling
- `rest.class.php` — REST API endpoints at `/wp-json/ppom/v1/`

### Two Runtime Modes

1. **Legacy mode** (`ppom_is_legacy_mode()`) — older rendering, cart fee-based pricing
2. **Modern mode** (default) — new template system, direct price modification

Conditional logic also has two versions: old (`ppom-conditions.js`) and new (`ppom-conditions-v2.js`, enabled via `ppom_get_conditions_mode() === 'new'`).

### Database

Custom table `{prefix}_nm_personalized` (constant `PPOM_TABLE_META`). Field groups are linked to products via the `_product_meta_id` post meta key.

### Key Constants

```php
PPOM_PATH              // Plugin directory path
PPOM_URL               // Plugin URL
PPOM_VERSION           // Current version string
PPOM_TABLE_META        // 'nm_personalized' (custom DB table name, without prefix)
PPOM_PRODUCT_META_KEY  // '_product_meta_id'
PPOM_UPLOAD_DIR_NAME   // 'ppom_files'
```

### Frontend Assets

JavaScript and CSS live in `js/` and `css/` (not compiled/bundled — loaded directly). Enqueuing handled by `classes/frontend-scripts.class.php`. Includes vendored libs: Bootstrap, Select2, Croppie, CodeMirror.

### Pro Feature Gating

Free vs Pro determined by `defined('PPOM_PRO_PATH')`. License tiers: `LICENSE_PLAN_FREE` (-1), `LICENSE_PLAN_1` (Essential), `LICENSE_PLAN_2` (Plus), `LICENSE_PLAN_3` (VIP). Freemium UI handled by `classes/freemium.class.php` and Themeisle SDK.

### WooCommerce Hook Flow

Product page → `woocommerce_before_add_to_cart_button` (render fields) → `woocommerce_add_to_cart_validation` (validate) → `woocommerce_add_cart_item_data` (save to cart) → `woocommerce_get_cart_item_from_session` (restore from session) → `woocommerce_checkout_create_order_line_item` (persist to order).

### HPOS Compatibility

Declares WooCommerce Custom Order Tables compatibility via `FeaturesUtil::declare_compatibility('custom_order_tables', ...)`.

## WooCommerce Security + Workflow

- Treat all input as untrusted (POST/AJAX/cart session/order meta).
- For state-changing actions, require both capability checks and nonce verification.
- Never trust frontend option pricing; recompute server-side in cart/checkout.
- Validate product/variation context with Woo objects before processing.
- Sanitize on input (type-aware) and escape on output (context-aware).
- Use `$wpdb->prepare()` (plus `$wpdb->esc_like()` for LIKE queries); never concatenate user input.
- For uploads, enforce extension/mime/size rules and block executable files.
- Prefer WooCommerce CRUD/order APIs (HPOS-safe) over direct post/meta SQL.
- Keep pricing hooks idempotent, especially in `woocommerce_before_calculate_totals`.
- Preferred option lifecycle hooks: `woocommerce_add_to_cart_validation` -> `woocommerce_add_cart_item_data` -> `woocommerce_get_cart_item_from_session` -> `woocommerce_get_item_data` -> `woocommerce_checkout_create_order_line_item`.
- Minimum regression checks per addon: simple/variable products, guest/logged-in checkout, tax modes, coupons/sales, session restore, and (if enabled) multi-currency/multi-language.
