# E2E Test Guidance

This folder contains Playwright tests for PPOM behavior in WordPress/WooCommerce.

## Default Approach

- Prefer stable server-side setup over admin UI setup.
- Use the `fixtures/` helpers for storefront, cart, checkout, rendering, pricing, and conditional-logic tests.
- Use `utils.js` only when the admin builder UI or attach modal UI is the feature under test.
- Keep UI setup smoke coverage thin. Do not make every E2E test create fields by clicking through the dashboard.

## Use `fixtures/` When

- The test only needs PPOM data to exist.
- You are validating product-page rendering, defaults, conditions, add-to-cart behavior, or checkout behavior.
- You need products or categories created quickly and deterministically.

Current fixture modules are split by concern:

- `fixtures/woocommerce.js` for WooCommerce entities
- `fixtures/ppom.js` for PPOM setup and attachments
- `fixtures/fields.js` for PPOM field builders
- `fixtures/internal.js` for shared request plumbing
- `fixtures/index.js` as the public entrypoint

Current fixture helpers support:

- WooCommerce product creation through `wc/v3/products`
- Product category creation through `wc/v3/products/categories`
- PPOM group creation through `ppom_save_form_meta`
- PPOM attachment through `ppom_attach_ppoms`

## Use `utils.js` When

- The admin builder itself is under test.
- The attach modal behavior itself is under test.
- The test needs to verify drag/drop, field ordering, modal controls, or admin-only interactions.

## Setup Rules

- Do not create products through wp-admin UI unless the UI is the thing being tested.
- For product setup, prefer authenticated REST requests through `requestUtils`.
- For PPOM group setup, prefer admin AJAX through the existing plugin callbacks instead of direct DB writes.
- Do not persist raw fixture assumptions that bypass plugin save hooks unless the test explicitly targets a lower layer.
- Admin UI specs can still use WooCommerce fixtures for prerequisite products and categories.

## Nonce Notes

- `ppom_form_nonce` is available on `wp-admin/admin.php?page=ppom&action=new`.
- `ppom_attached_nonce` is not present on the main PPOM index page.
- To attach groups, fetch the popup HTML from:
  `wp-admin/admin-ajax.php?action=ppom_get_products&ppom_id=<ppomId>`
- Scrape the attach nonce from that response before calling `ppom_attach_ppoms`.

## Flake Reduction

- Avoid using `waitForTimeout()` for state setup unless there is no deterministic alternative.
- Prefer request-level setup plus direct storefront assertions.
- Keep random data unique enough to avoid collisions across reruns.
- Avoid `console.log` in committed specs unless it is intentionally diagnostic.
- Assert fixture write success before navigating to the storefront.

## Test Design

- Separate admin smoke tests from storefront behavior tests.
- For conditions, pricing, and defaults, build the smallest field schema needed for the assertion.
- Prefer one clear behavior per test.
- If a flow depends on quantity, coupons, restore-from-session, or uploads, seed only the minimum data needed and assert the exact WooCommerce outcome.

## Verification

- If local E2E fails before tests start, check whether the WordPress env is reachable before debugging the spec.
- The current suite expects the wp-env site to be available and global setup to authenticate successfully.
- When changing fixtures, run the smallest spec set that exercises the changed helper first.
- If `wp-admin/admin.php?page=ppom` returns `403` while `wp-admin/profile.php` still works, treat that as an environment bootstrap issue first.
- In practice that usually means WooCommerce or PPOM is inactive in the local env, not that Playwright logged the user out.

## Practical Rule Of Thumb

- If the question is "does the product page behave correctly?", use fixtures.
- If the question is "does the PPOM admin interface work correctly?", use UI helpers.
