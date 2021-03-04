#!/bin/sh


echo "Cloning ComboDev"
git clone https://"${TOKEN}"@github.com/ComboStrap/combo_dev.git combo_dev
BASE="lib/plugins/combo"
echo "Moving into plugin directory: $BASE"
mv combo_dev/combo_test $BASE/_test

echo "Set up is done"
