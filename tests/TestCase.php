<?php

namespace Tests;

use AdvancedMigration\Constants;
use AdvancedMigration\MigrationGeneratorProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => Constants::SQLITE_DRIVER,
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            MigrationGeneratorProvider::class
        ];
    }
}
