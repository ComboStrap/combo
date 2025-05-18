# Contrib



## Main

The main entrypoint is the [docustom.php action script](action/docustom.php). ie
(ie a `do` custom action) that takes over action such as `show` (default).




## Laptop Dev Installation Steps


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
Use_autoloaoder: combo\_test\vendor\autoload.php
Use_default_configuration_file: combo/_test/phpunit.xml
Use_default_bootstrap_file: combo/lib/plugins/combo/_test/bootstrap.php
```
* Intellij Test Runner Configuration
```yaml
Use_alternative_configuration_file: combo/_test/phpunit.xml
Use_alternative_bootstrap_file: combo/lib/plugins/combo/_test/bootstrap.php
```
* Install Xdebug
