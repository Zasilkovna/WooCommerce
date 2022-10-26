#!/bin/bash

# Note that this does not use pipefail because if the grep later doesn't match I want to be able to show an error first
set -eo

# Ensure SVN username and password are set
# IMPORTANT: while secrets are encrypted and not viewable in the GitHub UI,
# they are by necessity provided as plaintext in the context of the Action,
# so do not echo or use debug mode unless you want your secrets exposed!
if [[ -z "$SVN_USERNAME" ]]; then
	echo "Set the SVN_USERNAME secret"
	exit 1
fi

if [[ -z "$SVN_PASSWORD" ]]; then
	echo "Set the SVN_PASSWORD secret"
	exit 1
fi

SVN_URL="https://plugins.svn.wordpress.org/${SLUG}/"
SVN_DIR="${HOME}/svn-${SLUG}"

# Checkout just trunk and assets for efficiency
# Tagging will be handled on the SVN level
echo "➤ Checking out .org repository..."
svn checkout --depth immediates "$SVN_URL" "$SVN_DIR"
cd "$SVN_DIR"
svn update --set-depth infinity trunk

echo "ℹ︎ Using .gitattributes"

cd "$GITHUB_WORKSPACE"

# "Export" a cleaned copy to a temp directory
TMP_DIR="${HOME}/archivetmp"
mkdir "$TMP_DIR"

git config --global user.email "technicka.podpora@zasilkovna.cz"
git config --global user.name "Technická podpora"

# This will exclude everything in the .gitattributes file with the export-ignore flag
git archive HEAD | tar x --directory="$TMP_DIR"

cd "$SVN_DIR"

# Copy from clean copy to /trunk, excluding dotorg assets
# The --delete flag will delete anything in destination that no longer exists in source
rsync -rc "$TMP_DIR/" trunk/ --delete --delete-excluded

# Add everything and commit to SVN
# The force flag ensures we recurse into subdirectories even if they are already added
# Suppress stdout in favor of svn status later for readability
echo "➤ Preparing files..."
svn add . --force > /dev/null

# SVN delete all deleted files
# Also suppress stdout here
svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm %@ > /dev/null

#Resolves => SVN commit failed: Directory out of date
svn update

svn status

echo "➤ Committing files..."
svn commit -m "Update to latest changes from GitHub" --no-auth-cache --non-interactive  --username "$SVN_USERNAME" --password "$SVN_PASSWORD"

echo "✓ All checked."
