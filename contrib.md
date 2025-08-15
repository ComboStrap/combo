# Contrib

## Info

### Main Class

The main entrypoint is the [docustom.php action script](action/docustom.php). ie
(ie a `do` custom action) that takes over action such as `show` (default).

### Dependencies Declaration

The dependencies are not in [plugins info](plugin.info.txt) but online in the `dependents`
property of the [Combo Plugin page](https://www.dokuwiki.org/plugin:combo)

It's used by the [new installer](https://www.patreon.com/posts/new-extension-116501986)

## How To
### How to install a new Laptop Dev Environment

* Install php7.4 on debian with
  the [sury repo](https://github.com/oerdnj/deb.sury.org/wiki/Frequently-Asked-Questions#how-to-enable-the-debsuryorg-repository)
* https://packages.sury.org/php/pool/main/p/php7.4/

```bash
curl -sSL https://packages.sury.org/php/README.txt | sudo bash -x
sudo apt update
sudo apt install -y php7.4 \
  php7.4-mbstring \
  php7.4-xml \
  php7.4-gd \
  php7.4-intl \
  php7.4-curl \
  php7.4-xdebug
# openssl not found
# curl is needed for snapshot
# pdo-sqlite seems to be installed with php7.4
which php7.4 # /usr/bin/php7.4
# ini
cat /etc/php/7.4/cli/php.ini
cat /etc/php/7.4/mods-available/xdebug.ini
```

* Clone Dokuwiki to get:
    * the base `_test\core\DokuWikiTest` class
    * and `_test\phpunit.xml`

```bash
git clone https://github.com/dokuwiki/dokuwiki combo
cd combo
```

* Clone Combo

```bash
cd dokuwiki_home/lib/plugins/
git clone git@github.com:ComboStrap/combo
```

* Clone the dependent plugins found in [requirements](requirements.txt)

```bash
cd dokuwiki_home/lib/plugins/
git clone https://github.com/cosmocode/sqlite sqlite
git clone https://github.com/michitux/dokuwiki-plugin-move/ move
git clone https://github.com/dokufreaks/plugin-include include
git clone https://github.com/tatewake/dokuwiki-plugin-googleanalytics googleanalytics
git clone https://github.com/alexlehm/dokuwiki-plugin-gtm googletagmanager
```

* Clone the tests
```bash
cd dokuwiki_home/lib/plugins/combo
git clone git@github.com:ComboStrap/combo_test.git _test
```

* Install Node dependency
```bash
cd dokuwiki_home/lib/plugins/combo
npm install
```

* Install phpunit

```bash
cd dokuwiki_home/_test
composer install
```



### Intellij Php WSL

* Git
    * add lib/plugins/combo as a registered root (Intellij> Version Control > Directory Mapping)
    * Check that Set it as a source root

Following [](https://www.jetbrains.com/help/phpstorm/how-to-use-wsl-development-environment-in-product.html#open-a-project-in-wsl)

* Install the plugin PHP WSL Support
* Add Php Cli Interpreter on WSL. Intellij > Settings > Php > Cli Interpreter
* Firewall from an elevated PowerShell

```powershell
New-NetFirewallRule -DisplayName "WSL" -Direction Inbound  -InterfaceAlias "vEthernet (WSL (Hyper-V firewall))"  -Action Allow
Get-NetFirewallProfile -Name Public | Get-NetFirewallRule | where DisplayName -ILike "IntelliJ IDEA*" | Disable-NetFirewallRule
```

* Intellij > Settings > Php > Test Framework

```yaml
Use_autoloader: combo\_test\vendor\autoload.php
Use_default_configuration_file: combo/_test/phpunit.xml
Use_default_bootstrap_file: combo/lib/plugins/combo/_test/bootstrap.php
```

* Intellij Test Runner Configuration

```yaml
Use_alternative_configuration_file: combo/_test/phpunit.xml
Use_alternative_bootstrap_file: combo/lib/plugins/combo/_test/bootstrap.php
Interpreter_cli: use wsl
Interpreter_options: |
    # put this ip if you are not in mirrored mode and intellij keep using 127.0.0.1
    -dxdebug.client_host=$(echo $(ip route list default | awk '{print $3}'))
```


## Start it

```bash
docker run \
  --name combo \
  -d \
  -p 8082:80 \
  --user 1000:1000 \
  -e DOKU_DOCKER_ENV=dev \
  -e DOKU_DOCKER_ACL_POLICY='public' \
  -e DOKU_DOCKER_ADMIN_NAME='admin' \
  -e DOKU_DOCKER_ADMIN_PASSWORD='welcome' \
  -v $PWD:/var/www/html \
  ghcr.io/combostrap/dokuwiki:php8.3-latest
```


## Release

* Change the date in the [plugin.info](plugin.info.txt)
* Commit
* Create a Release on GitHub that points to the release page of https://combostrap.com
* Modify the pages
  * https://www.dokuwiki.org/plugin:combo
  * https://www.dokuwiki.org/template:strap

