<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd70cb9592dac288bac3f582617e57449
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
            $loader->prefixLengthsPsr4 = ComposerStaticInitd70cb9592dac288bac3f582617e57449::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd70cb9592dac288bac3f582617e57449::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd70cb9592dac288bac3f582617e57449::$classMap;

        }, null, ClassLoader::class);
    }
}
