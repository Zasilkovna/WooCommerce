#!/bin/bash

echo "Removing old file..."
rm languages/packeta.pot

echo "Generating new translations file..."
./vendor/bin/wp i18n make-pot . languages/packeta.pot --allow-root

if id -u www-data &>/dev/null && getent group www-data &>/dev/null; then
	echo "Changing owner..."
	chown www-data:www-data languages/packeta.pot
else
	echo "Warning: not changing owner."
fi

echo "Done."
