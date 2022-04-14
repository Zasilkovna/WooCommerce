#!/bin/bash

# Note that this does not use pipefail because if the grep later doesn't match I want to be able to show an error first
set -eo

echo "ℹ︎ PHP version:"
php -v | head -1

echo "ℹ︎ Composer version:"
composer -V

echo "➤ Installing woocommerce/woocommerce-sniffs:"
composer -n -q require woocommerce/woocommerce-sniffs

echo "➤ Running sniffer:"
./vendor/bin/phpcs | tee /tmp/sniffer.log

if grep -q '| ERROR' /tmp/sniffer.log; then
  echo "🛑 Sniffer found errors, fix them."
  exit 1
fi

echo "✓ All checked."
