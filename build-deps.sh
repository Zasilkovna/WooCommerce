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

# Composer writes the Composer\InstalledVersions key to the class map without the prefix.
# When other code checks Composer\InstalledVersions, our autoloader includes the file again
# and PHP declares the prefixed class twice.
OLD_KEY='Composer\\\\InstalledVersions'
NEW_KEY='Packetery\\\\Composer\\\\InstalledVersions'
for CLASS_MAP in deps/composer/autoload_classmap.php deps/composer/autoload_static.php; do
	sed -i.bak "s/'$OLD_KEY'/'$NEW_KEY'/" "$CLASS_MAP"
	rm -f "$CLASS_MAP.bak"
done

# While pushing to svn using "deploy" pipeline, a static code check is run that these examples containing
# incorrect syntax would not pass. We prefer this solution over post-update-cmd.
rm -rf deps/tracy/tracy/examples
