<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInita9a9d08ae1e89477796f4edf3c69c6e7
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInita9a9d08ae1e89477796f4edf3c69c6e7', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInita9a9d08ae1e89477796f4edf3c69c6e7', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInita9a9d08ae1e89477796f4edf3c69c6e7::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
