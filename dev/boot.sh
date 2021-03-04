#!/bin/sh


echo "Cloning ComboDev"
git clone https://"${TOKEN}"@github.com/ComboStrap/combo_dev.git combo_dev
mv combo_dev/combo_test ./_test

echo "Set up is done"
