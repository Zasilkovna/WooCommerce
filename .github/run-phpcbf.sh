#!/bin/bash

./vendor/bin/phpcbf
exit_code=$?

# Check if phpcbf made changes (non-zero exit code indicates fixes were made)
if [ $exit_code -ne 0 ]; then
  echo "🛑 phpcbf made changes to the code."
  exit 1
else
  echo "✓ no changes were made by phpcbf."
  exit 0
fi
