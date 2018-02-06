<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit13ffe964fa47366893f6d480a6358d77
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PublishPress\\EDD_License\\Core\\' => 30,
            'Psr\\Container\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PublishPress\\EDD_License\\Core\\' => 
        array (
            0 => __DIR__ . '/..' . '/publishpress/wordpress-edd-license-integration/src/core',
        ),
        'Psr\\Container\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/container/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Pimple' => 
            array (
                0 => __DIR__ . '/..' . '/pimple/pimple/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit13ffe964fa47366893f6d480a6358d77::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit13ffe964fa47366893f6d480a6358d77::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit13ffe964fa47366893f6d480a6358d77::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
