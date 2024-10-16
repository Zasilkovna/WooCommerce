#!/bin/bash

# Note that this does not use pipefail because if the grep later doesn't match I want to be able to show an error first
set -eo

echo "ℹ︎ PHP version:"
php --version | head -1

echo "ℹ︎ Composer version:"
composer --version

echo "➤ Installing woocommerce/woocommerce-sniffs:"
composer --no-interaction --quiet --no-dev install

echo "➤ Running sniffer:"
./vendor/bin/phpcs -s | tee /tmp/sniffer.log

if grep -q '| ERROR' /tmp/sniffer.log; then
	echo "🛑 Sniffer found errors, fix them."
	exit 1
fi

echo "✓ All checked."
