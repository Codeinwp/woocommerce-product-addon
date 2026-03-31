# Run after `npm run wp-env start`

# Add some woocommerce products.
npm run wp-env run tests-cli bash ./wp-content/plugins/woocommerce-product-addon/bin/env/create-products.sh

# Seed the quantity-matrix fixture data used by critical storefront E2E coverage.
npm run wp-env run tests-cli bash ./wp-content/plugins/woocommerce-product-addon/bin/env/create-quantity-matrix-group.sh
