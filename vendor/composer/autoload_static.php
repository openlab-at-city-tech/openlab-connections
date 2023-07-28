<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9722a67b02c6e10d1d8e39760396b194
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'OpenLab\\Connections\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'OpenLab\\Connections\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9722a67b02c6e10d1d8e39760396b194::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9722a67b02c6e10d1d8e39760396b194::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9722a67b02c6e10d1d8e39760396b194::$classMap;

        }, null, ClassLoader::class);
    }
}