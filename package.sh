#!/bin/bash -e

VERSION=$1

BRANCH=${2:-"dev"}

if [[ ! "$VERSION"  ]]; then
  echo "Usage: $0 <version> [branch]"
  exit 1
fi

ORIGINAL_DIR=$PWD

OUTPUT_FILE=$ORIGINAL_DIR/mlinvoice-$VERSION.zip

MLINVOICE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

TMP_DIR=`mktemp -d`

if [[ ! "$TMP_DIR" || ! -d "$TMP_DIR" ]]; then
  echo "Could not create temp dir"
  exit 1
fi

function cleanup {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT

cd $MLINVOICE_DIR
git archive --format zip --prefix mlinvoice/ --output $OUTPUT_FILE $BRANCH
git log v1.10.0..HEAD --diff-filter=D --summary | grep 'delete mode' | cut -d " " -f 5- > $TMP_DIR/obsolete_files.txt
cd $TMP_DIR
unzip $OUTPUT_FILE
mv obsolete_files.txt mlinvoice/
cd mlinvoice
composer install --no-dev
npm install
grunt uglify sass
# Make sure to keep node_modules/.htaccess:
rm -rf node_modules/*
npm install --production
find . -type f -size 0 -delete
cd ..
zip -r $OUTPUT_FILE mlinvoice

echo "$OUTPUT_FILE successfully created from branch $BRANCH"