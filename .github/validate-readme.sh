#!/bin/bash

# Note that this does not use pipefail because if the grep later doesn't match I want to be able to show an error first
set -eo

echo "ℹ︎ PHP version:"
php -v | head -1

echo "➤ Running validation:"
cd "$(dirname "$0")"
php validate-readme.php | tee /tmp/validate-readme.log

if grep -q 'ERROR' /tmp/validate-readme.log; then
  echo "🛑 Validator found errors, fix them."
  exit 1
fi

echo "✓ All checked."
