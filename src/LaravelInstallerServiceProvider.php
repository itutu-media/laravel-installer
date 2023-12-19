<?php

namespace ITUTUMedia\LaravelInstaller;

use ITUTUMedia\LaravelInstaller\Commands\LaravelInstallerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelInstallerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-installer')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-installer_table')
            ->hasCommand(LaravelInstallerCommand::class);
    }
}
