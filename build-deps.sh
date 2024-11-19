#!/bin/bash

if command -v composer &>/dev/null; then
	COMPOSER_CMD="composer"
elif [ -f "./composer.phar" ]; then
	COMPOSER_CMD="php ./composer.phar"
else
	echo "Error: Composer is not available. Neither 'composer' command nor 'composer.phar' was found." >&2
	exit 1
fi

$COMPOSER_CMD update
$COMPOSER_CMD wpify-scoper update

# While pushing to svn using "deploy" pipeline, a static code check is run that these examples containing
# incorrect syntax would not pass. We prefer this solution over post-update-cmd.
rm -rf deps/tracy/tracy/examples
