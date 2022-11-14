#!/usr/bin/env bash

docker run \
  --user root \
  --rm \
  --volume  "$(pwd):/var/www/html/woocommerce-product-addon" \
  wordpress:cli bash -c 'php -d memory_limit=1024M "$(which wp)" i18n make-pot ./woocommerce-product-addon/ ./woocommerce-product-addon/languages/woocommerce-product-addon.pot --headers={\"Last-Translator\":\"friends@themeisle.com\"\,\"Project-Id-Version\":\"PPOM\"\,\"Report-Msgid-Bugs-To\":\"https://github.com/Codeinwp/woocommerce-product-addon/issues\"\} --allow-root --exclude=dist,build,bundle,e2e-tests '