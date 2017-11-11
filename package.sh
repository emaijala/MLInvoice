#!/bin/bash -e

VERSION=$1

if [[ ! "$VERSION"  ]]; then
  echo "Usage: $0 <version>"
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
git archive --format zip --prefix mlinvoice/ --output $OUTPUT_FILE master
cd $TMP_DIR
unzip $OUTPUT_FILE
cd mlinvoice
composer install --no-dev
cd ..
zip -r $OUTPUT_FILE mlinvoice

echo "$OUTPUT_FILE successfully created"