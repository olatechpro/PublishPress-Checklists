<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit13ffe964fa47366893f6d480a6358d77
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PressShack\\EDD_License\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PressShack\\EDD_License\\' => 
        array (
            0 => __DIR__ . '/..' . '/pressshack/wordpress-edd-license-integration/lib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit13ffe964fa47366893f6d480a6358d77::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit13ffe964fa47366893f6d480a6358d77::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
