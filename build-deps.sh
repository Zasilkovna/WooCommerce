#!/bin/bash

php composer.phar update
php composer.phar wpify-scoper update

# While pushing to svn using "deploy" pipeline, a static code check is run that these examples containing
# incorrect syntax would not pass. We prefer this solution over post-update-cmd.
rm -rf deps/tracy/tracy/examples
