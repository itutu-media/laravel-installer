<?php

namespace ITUTUMedia\LaravelInstaller\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ITUTUMedia\LaravelInstaller\LaravelInstaller
 */
class LaravelInstaller extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \ITUTUMedia\LaravelInstaller\LaravelInstaller::class;
    }
}
