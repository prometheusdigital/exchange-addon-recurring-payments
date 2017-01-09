<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0bc17dcce4deab0ed3d0d16888b2f8a4
{
    public static $prefixLengthsPsr4 = array (
        'i' => 
        array (
            'iThemes\\Exchange\\RecurringPayments\\REST\\' => 40,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'iThemes\\Exchange\\RecurringPayments\\REST\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib/REST',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0bc17dcce4deab0ed3d0d16888b2f8a4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0bc17dcce4deab0ed3d0d16888b2f8a4::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
