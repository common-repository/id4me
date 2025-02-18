<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit26aeda944456a6a2083b3bfbdf14e141
{
    public static $files = array (
        'decc78cc4436b1292c6c0d151b19445c' => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib\\' => 10,
        ),
        'I' => 
        array (
            'Id4me\\Test\\' => 11,
            'Id4me\\RP\\' => 9,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'Id4me\\Test\\' => 
        array (
            0 => __DIR__ . '/..' . '/id4me/id4me-rp/tests',
        ),
        'Id4me\\RP\\' => 
        array (
            0 => __DIR__ . '/..' . '/id4me/id4me-rp/src',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit26aeda944456a6a2083b3bfbdf14e141::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit26aeda944456a6a2083b3bfbdf14e141::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit26aeda944456a6a2083b3bfbdf14e141::$classMap;

        }, null, ClassLoader::class);
    }
}
