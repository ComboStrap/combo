# Contrib



## Main

The main entrypoint is the [docustom.php action script](action/docustom.php). ie
(ie a `do` custom action) that takes over action such as `show` (default).




## Laptop Dev Installation Steps

* Install php7.4 on debian with the [sury repo](https://github.com/oerdnj/deb.sury.org/wiki/Frequently-Asked-Questions#how-to-enable-the-debsuryorg-repository)
* https://packages.sury.org/php/pool/main/p/php7.4/
```bash
curl -sSL https://packages.sury.org/php/README.txt | sudo bash -x
sudo apt update
sudo apt install -y php7.4 \
  php7.4-mbstring \
  php7.4-xml \
  php7.4-gd \
  php7.4-intl \
  php7.4-xdebug \
  php7.4-sqlite3
# openssl not found
which php7.4
# ini
cat /etc/php/7.4/cli/php.ini
cat /etc/php/7.4/mods-available/xdebug.ini
```

* Clone Dokuwiki to get:
  * the base DokuWikiTest class
  * and `_test\phpunit.xml`
```bash
git clone https://github.com/dokuwiki/dokuwiki combo
cd combo
```
* Clone Combo
```bash
cd lib/plugins/
git clone git@github.com:ComboStrap/combo
```
* Intellij
    * add it as registered root (Intellij> Version Control > Directory Mapping)
    * Set it as source root
* Clone the tests
```bash
cd combo
git clone git@github.com:ComboStrap/combo_test.git _test
```
* Install phpunit
```bash
cd dokuwiki/_test
composer install
```

### Intellij Php WSL


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
