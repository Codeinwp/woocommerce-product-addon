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
npm run env:setup          # Start WordPress Docker environment
npm run test:unit:php                # Run PHPUnit tests

# Run a single PHPUnit test file
wp-env run --env-cwd='wp-content/plugins/woocommerce-product-addon' tests-wordpress vendor/bin/phpunit -c phpunit.xml --filter TestClassName

# E2E tests (Playwright, Chromium only) that runs on docker.
npm run test:e2e
npm run test:e2e:debug               # Opens Playwright UI

# Static analysis
composer run phpstan                  # PHPStan level 6
composer run phpstan:generate:baseline
```

You can also use `agent-browser` CLI if available with WP Docker environments for a more interactive testing experience, with credentials:

```
Username: admin
Password: password
```

## Code Quality

- **PHP standard**: Themeisle ruleset (WordPress-based) via `phpcs.xml`. Text domain: `woocommerce-product-addon`.
- **PHPStan**: Level 8 with a large baseline file (`phpstan-baseline.neon`). Scans `inc/`, `classes/`, `backend/`, `templates/`, and the main plugin file.
- **Min PHP**: 7.4
- **PHPDoc `@see`**: Use for cross-file related entry points (especially `inc/` versus `classes/`). Optional file-level docblocks may list **two to four** canonical functions or methods as a small index; avoid long or misleading lists—use `./ARCHITECTURE.md` and the overview above for layout.

## Architecture

You can read more about it on `./ARCHITECTURE.md`, but here’s a high-level overview of the main components and their relationships.

### Entry Point & Bootstrap

`woocommerce-product-addon.php` — defines constants, loads Composer autoload, manually `require_once`s all class/include files (no PSR-4 autoloading for plugin code), then hooks `PPOM()` on `woocommerce_init`.

### Core Classes

| Class                          | File                       | Role                                                                                                            |
| ------------------------------ | -------------------------- | --------------------------------------------------------------------------------------------------------------- |
| `NM_PersonalizedProduct`       | `classes/plugin.class.php` | Main plugin — registers all WooCommerce hooks, loads input types                                                |
| `NM_PersonalizedProduct_Admin` | `classes/admin.class.php`  | Admin-only coordinator for menus, settings, attach flows, and admin AJAX                                        |
| `PPOM_Meta`                    | `classes/ppom.class.php`   | Product-side field-group resolver that reads attached groups and loads settings/fields from the custom DB table |
| `PPOM_Form`                    | `classes/form.class.php`   | Frontend form rendering                                                                                         |
| `PPOM_Fields_Meta`             | `classes/fields.class.php` | Admin field-builder UI, modals, and builder asset loading                                                       |
| `PPOM_Inputs`                  | `classes/input.class.php`  | Input type manager                                                                                              |

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

### Trust Boundaries

- Treat product page fields, AJAX/REST payloads, cart item data, restored sessions, order item meta, and admin imports/settings as untrusted input.
- Never trust browser-sent prices, fee amounts, labels, field IDs, variation IDs, conditional flags, upload metadata, or Pro gating flags. Recompute from saved PPOM/WooCommerce configuration on the server.
- Validate submitted field names/options against the field schema attached to the current product/meta group before storing or pricing anything.
- Resolve product and variation IDs to real WooCommerce objects and confirm the variation belongs to the parent product before processing.

### Authorization + Request Integrity

- For every state-changing admin, AJAX, or REST action, require both a capability check and nonce verification. For REST routes, always implement a strict `permission_callback`.
- Never use `is_admin()` as an authorization check.
- Scope privileged actions to the narrowest capability that fits the action: field-group CRUD, settings changes, file deletion, import/export, license actions, and diagnostic tools should not share a blanket permission model.

### Data Handling Rules

- Sanitize on input with type-appropriate functions, validate against business rules, and escape on output with the correct context-aware `esc_*()` function.
- Use `$wpdb->prepare()` for every query containing dynamic input, and pair `LIKE` clauses with `$wpdb->esc_like()`. Never concatenate request data into SQL.
- Prefer WooCommerce CRUD APIs and order/item meta APIs over direct post/meta SQL so behavior stays HPOS-safe.
- Do not persist raw `$_POST` or `$_REQUEST` payloads into cart item data, session data, or order meta. Store only the normalized values the plugin actually needs.
- Do not expose addon values, upload URLs, or order item metadata in logs, notices, REST responses, emails, or templates unless the current user/context is explicitly allowed to see them.

### Pricing + Cart Integrity

- Keep all pricing logic server-authoritative and idempotent. Hooks like `woocommerce_before_calculate_totals` may run multiple times per request.
- Recalculate addon totals from canonical field definitions during validation, cart restore, and checkout instead of trusting values carried forward from the browser or session.
- Guard against double-charging when cart items are restored from session, when quantities change, or when multiple pricing hooks run in sequence.
- When pricing depends on product type, variation, quantity, tax mode, currency, or coupon state, use current WooCommerce objects/state at calculation time instead of stale cart snapshots.

### Upload Safety

- Enforce an allowlist for extensions, MIME types, and size limits; reject executable/scriptable files, double extensions, and unexpected archive types.
- Generate filenames and paths server-side, keep uploads inside the dedicated PPOM upload directory, and never trust client-provided path, MIME, or filename values.
- Re-check authorization and attachment ownership before serving, deleting, or attaching uploaded files to cart/order data.

### WooCommerce Lifecycle

- Preferred option lifecycle hooks: `woocommerce_add_to_cart_validation` -> `woocommerce_add_cart_item_data` -> `woocommerce_get_cart_item_from_session` -> `woocommerce_get_item_data` -> `woocommerce_checkout_create_order_line_item`.
- Validate as early as possible, normalize before storing in cart data, and only persist to order items after the cart payload has been revalidated.
- Treat session restore, reorder, and edit-cart flows as fresh untrusted input, not trusted historical state.

### Minimum Regression Checks

- Per addon/security-sensitive change, cover simple and variable products, guest and logged-in checkout, tax modes, coupons/sales, quantity changes, and session restore.
- If uploads are involved, test invalid MIME/extension cases, oversized files, duplicate filenames, and cleanup paths.
- If REST, AJAX, or admin writes are involved, explicitly test success, nonce failure, and capability failure paths.
- If enabled in the target store, also verify multi-currency, multi-language, and HPOS behavior.
