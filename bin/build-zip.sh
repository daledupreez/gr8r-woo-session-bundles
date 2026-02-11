#!/usr/bin/env bash

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
REPO_DIR="$( cd "$SCRIPT_DIR/../" > /dev/null 2>&1 && pwd )"

cd $REPO_DIR


if [ -d "./build" ]; then
    rm -rf ./build
fi

mkdir -p build

cp -R ./assets ./build/
cp -R ./includes ./build/
cp -R ./languages ./build/
cp ./gr8r-woo-session-bundles.php ./build/

cd ./build && zip -r gr8r-woo-session-bundles.zip . ; cd -
