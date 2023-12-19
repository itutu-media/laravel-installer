<?php

namespace ITUTUMedia\LaravelInstaller\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use ITUTUMedia\LaravelInstaller\LaravelInstallerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'ITUTUMedia\\LaravelInstaller\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelInstallerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-installer_table.php.stub';
        $migration->up();
        */
    }
}
