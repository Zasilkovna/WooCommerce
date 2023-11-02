#!/bin/bash

# Note that this does not use pipefail because if the grep later doesn't match I want to be able to show an error first
set -eo

echo "ℹ︎ PHP version:"
php -v | head -1

echo "ℹ︎ Composer version:"
composer -V

echo "➤︎ Clean up:"
rm ./composer.json ./composer.lock ./composer-deps.json ./composer-deps.lock
echo "{}" > composer.json
composer config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true

echo "➤ Installing woocommerce/woocommerce-sniffs:"
composer -n -q require woocommerce/woocommerce-sniffs:0.1.3

echo "➤ Running sniffer:"
./vendor/bin/phpcs | tee /tmp/sniffer.log

if grep -q '| ERROR' /tmp/sniffer.log; then
  echo "🛑 Sniffer found errors, fix them."
  exit 1
fi

echo "✓ All checked."
