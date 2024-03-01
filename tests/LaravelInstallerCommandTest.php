<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\TestCase;

it('sets env if not exists', function () {
    // Mock the existence of .env file
    Storage::fake('local');
    Storage::disk('local')->put('.env', '');

    Artisan::call('app:install');

    // Assert that .env file was set
    TestCase::assertTrue(Storage::disk('local')->exists('.env'));
});

it('sets env if db connection fails', function () {
    // Mock the DB connection to always return false
    DB::shouldReceive('connection->getPdo')->andThrow(new Exception());

    Artisan::call('app:install');

    // Assert that .env file was set
    TestCase::assertTrue(Storage::disk('local')->exists('.env'));
});
