#!/usr/bin/env bash

set -eu

fixture_path="./wp-content/plugins/woocommerce-product-addon/tests/e2e/fixtures/create-quantity-matrix-group.php"
output_path="./wp-content/plugins/woocommerce-product-addon/tests/e2e/fixtures/generated/quantity-matrix-group.json"
suffix="${1:-e2e_quantity_matrix}"

mkdir -p "$(dirname "$output_path")"

fixture_output="$(wp eval-file "$fixture_path" "$suffix")"
json_line="$(printf '%s\n' "$fixture_output" | sed '/^[[:space:]]*$/d' | tail -n 1)"

if [ -z "$json_line" ]; then
	echo "Failed to seed the quantity-matrix fixture." >&2
	exit 1
fi

printf '%s\n' "$json_line" > "$output_path"
