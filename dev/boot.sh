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
git clone https://github.com/splitbrain/dokuwiki.git .

echo "Cloning ComboDev"
git clone https://"${TOKEN}"@github.com/ComboStrap/combo_dev.git combo_dev
ln -s combo_dev/combo_test lib/plugins/combo/_test

