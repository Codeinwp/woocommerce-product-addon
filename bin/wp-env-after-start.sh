#!/usr/bin/env bash

set -euo pipefail

WP_ENV_BIN="${WP_ENV_BIN:-./node_modules/.bin/wp-env}"

if [ ! -x "$WP_ENV_BIN" ]; then
	echo "wp-env binary not found at $WP_ENV_BIN" >&2
	exit 1
fi

activate_plugin() {
	local env="$1"
	local plugin="$2"

	echo "Activating $plugin in $env"
	"$WP_ENV_BIN" run "$env" wp plugin activate "$plugin"
}

activate_plugin cli woocommerce
activate_plugin cli woocommerce-product-addon
activate_plugin tests-cli woocommerce
activate_plugin tests-cli woocommerce-product-addon
