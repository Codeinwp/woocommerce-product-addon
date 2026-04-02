# E2E Test Guidance

This folder contains Playwright tests for PPOM behavior in WordPress/WooCommerce.

## Default Approach

- Prefer stable server-side setup over admin UI setup.
- Use the `fixtures/` helpers for storefront, cart, checkout, rendering, pricing, and conditional-logic tests.
- For complex setup needs, extend the wp-env bootstrap MU plugin at `bin/wp-env/mu-plugins/ppom-e2e-bootstrap.php` and then expose that behavior through `fixtures/`.
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

- WooCommerce simple and variable product creation through the E2E bootstrap MU plugin
- WooCommerce variation and category creation through the E2E bootstrap MU plugin
- PPOM group creation and attachment through the E2E bootstrap MU plugin
- Environment cleanup/reset through the E2E bootstrap MU plugin

## Use `utils.js` When

- The admin builder itself is under test.
- The attach modal behavior itself is under test.
- The test needs to verify drag/drop, field ordering, modal controls, or admin-only interactions.

## Setup Rules

- Do not create products through wp-admin UI unless the UI is the thing being tested.
- For product, variation, category, and PPOM setup, prefer the `fixtures/` layer backed by the bootstrap MU plugin.
- If an existing fixture cannot express the scenario, add or extend a bootstrap action in `bin/wp-env/mu-plugins/ppom-e2e-bootstrap.php`, then wrap it in `fixtures/`.
- Do not put raw setup calls directly in specs when the same behavior belongs in a reusable fixture helper.
- Use the bootstrap reset path for cleanup; do not rely on leftover state from previous runs.
- Do not persist raw fixture assumptions that bypass plugin save hooks unless the test explicitly targets a lower layer.
- Admin UI specs can still use WooCommerce fixtures for prerequisite products and categories.

## Complex Scenarios

- When a test needs a richer state shape such as variable products, multi-entity attachments, cleanup/reset, or future seeded catalog flows, modify the bootstrap MU plugin first and keep the spec itself fixture-driven.
- Add composable bootstrap primitives rather than one-off scenario code inside a spec whenever the setup could be reused.
- Keep the public fixture API stable where possible: specs should import from `fixtures/index.js`, not know bootstrap action names.
- If a scenario truly belongs to admin UI coverage, keep the setup minimal and use `utils.js` only for the UI portion under test.

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
- When changing the bootstrap MU plugin, rerun the smallest fixture-driven spec that uses the new action plus the bootstrap-specific smoke coverage.
- If `wp-admin/admin.php?page=ppom` returns `403` while `wp-admin/profile.php` still works, treat that as an environment bootstrap issue first.
- In practice that usually means WooCommerce or PPOM is inactive in the local env, not that Playwright logged the user out.

## Practical Rule Of Thumb

- If the question is "does the product page behave correctly?", use fixtures.
- If the question is "does the PPOM admin interface work correctly?", use UI helpers.
