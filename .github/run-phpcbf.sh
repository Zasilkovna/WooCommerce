#!/bin/bash

# Run phpcbf and capture output
output=$(./vendor/bin/phpcbf "$@")
exit_code=$?

echo $output

# Check if phpcbf made changes (non-zero exit code indicates fixes were made)
if [ $exit_code -ne 0 ]; then
  echo "phpcbf made changes to the code."
  exit 1
else
  echo "No changes were made by phpcbf."
  exit 0
fi
