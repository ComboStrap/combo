#!/bin/sh

echo "Travis or gitlab-ci check"
if [ -z "$TRAVIS" ] && [ -z "$CI_SERVER" ]  ; then
    echo 'This is not a continuous server'
    exit 1
fi

echo "Moving the code to the plugin directory: $BASE"
BASE="lib/plugins/combo"
mkdir -p $BASE
mv ./* $BASE 2>/dev/null
mv .[a-zA-Z0-9_-]* $BASE

echo "Cloning DokuWiki into the current directory"
# git ini and git pull to avoid the error: `fatal: destination path '.' already exists and is not an empty directory`
git init
git pull https://github.com/splitbrain/dokuwiki.git

echo "Cloning Additional Plugin Dependency"
REQUIRE="$BASE/requirements.txt"
if [ -f "$REQUIRE" ]; then
    grep -v '^#' "$REQUIRE" | \
    while read -r DEPENDENCY_URL DEPENDENCY_DIR_TARGET
    do
        if [ -n "$DEPENDENCY_URL" ]; then
            echo ">Cloning Dependency: $DEPENDENCY_URL into $DEPENDENCY_DIR_TARGET"
            git clone "$DEPENDENCY_URL" "$DEPENDENCY_DIR_TARGET"
        fi
    done
fi

echo "Cloning ComboDev"
git clone https://"${TOKEN}"@github.com/ComboStrap/combo_dev.git combo_dev
mv combo_dev/combo_test $BASE/_test

echo "Download phpunit"
cd _test
if [ ! -f "fetchphpunit.php" ]; then
    wget https://raw.githubusercontent.com/splitbrain/dokuwiki/master/_test/fetchphpunit.php
    chmod 755 fetchphpunit.php
fi
php ./fetchphpunit.php
cd ..

echo "Set up is done"
