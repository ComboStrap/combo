<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb17e1b0ee5884bcef9ce08e1c1e6b764
{
    public static $files = array (
        'a4a119a56e50fbb293281d9a48007e0e' => __DIR__ . '/..' . '/symfony/polyfill-php80/bootstrap.php',
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '320cde22f66dd4f5d3fd621d3e88b98f' => __DIR__ . '/..' . '/symfony/polyfill-ctype/bootstrap.php',
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        'd7c9a5138b45deb428e175ae748db2c5' => __DIR__ . '/..' . '/carica/phpcss/src/PhpCss.php',
        '2a3c2110e8e0295330dc3d11a4cbc4cb' => __DIR__ . '/..' . '/php-webdriver/webdriver/lib/Exception/TimeoutException.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Php80\\' => 23,
            'Symfony\\Polyfill\\Mbstring\\' => 26,
            'Symfony\\Polyfill\\Ctype\\' => 23,
            'Symfony\\Component\\Yaml\\' => 23,
            'Symfony\\Component\\Process\\' => 26,
        ),
        'P' => 
        array (
            'PhpCss\\' => 7,
        ),
        'H' => 
        array (
            'Hidehalo\\Nanoid\\' => 16,
        ),
        'F' => 
        array (
            'Facebook\\WebDriver\\' => 19,
        ),
        'C' => 
        array (
            'Cron\\' => 5,
            'ComboStrap\\' => 11,
        ),
        'A' => 
        array (
            'Antlr\\Antlr4\\Runtime\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Polyfill\\Php80\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-php80',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Symfony\\Polyfill\\Ctype\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-ctype',
        ),
        'Symfony\\Component\\Yaml\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/yaml',
        ),
        'Symfony\\Component\\Process\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/process',
        ),
        'PhpCss\\' => 
        array (
            0 => __DIR__ . '/..' . '/carica/phpcss/src/PhpCss',
        ),
        'Hidehalo\\Nanoid\\' => 
        array (
            0 => __DIR__ . '/..' . '/hidehalo/nanoid-php/src',
        ),
        'Facebook\\WebDriver\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-webdriver/webdriver/lib',
        ),
        'Cron\\' => 
        array (
            0 => __DIR__ . '/..' . '/dragonmantank/cron-expression/src/Cron',
        ),
        'ComboStrap\\' => 
        array (
            0 => __DIR__ . '/../..' . '/ComboStrap',
            1 => __DIR__ . '/../..' . '/_test/ComboStrap',
        ),
        'Antlr\\Antlr4\\Runtime\\' => 
        array (
            0 => __DIR__ . '/..' . '/antlr/antlr4-php-runtime/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'H' => 
        array (
            'Handlebars' => 
            array (
                0 => __DIR__ . '/..' . '/salesforce/handlebars-php/src',
            ),
        ),
    );

    public static $classMap = array (
        'Attribute' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Attribute.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'PhpToken' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/PhpToken.php',
        'Stringable' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Stringable.php',
        'UnhandledMatchError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/UnhandledMatchError.php',
        'ValueError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/ValueError.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb17e1b0ee5884bcef9ce08e1c1e6b764::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb17e1b0ee5884bcef9ce08e1c1e6b764::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitb17e1b0ee5884bcef9ce08e1c1e6b764::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitb17e1b0ee5884bcef9ce08e1c1e6b764::$classMap;

        }, null, ClassLoader::class);
    }
}
