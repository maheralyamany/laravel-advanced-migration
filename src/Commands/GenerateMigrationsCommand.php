<?php

namespace AdvancedMigration\Commands;

use AdvancedMigration\Constants;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use AdvancedMigration\GeneratorManagers\Interfaces\GeneratorManagerInterface;
use AdvancedMigration\GeneratorManagers\MySQLGeneratorManager;
use AdvancedMigration\GeneratorManagers\PostgresGeneratorManager;
use AdvancedMigration\GeneratorManagers\SQLiteGeneratorManager;
use AdvancedMigration\GeneratorManagers\SqlServerGeneratorManager;
use AdvancedMigration\Helpers\ConfigResolver;

class GenerateMigrationsCommand extends Command
{
    protected $signature = 'generate:advanced-migrations {--path=default : The path where migrations will be output to} {--table= : Only generate output for specified tables} {--view=* : Only generate output for specified views} {--connection=default : Use a different database connection specified in database config} {--empty-path : Clear other files in path, eg if wanting to replace all migrations}';

    protected $description = 'Generate migrations from an existing database';

    public function getConnection()
    {
        $connection = $this->option('connection');

        if ($connection === 'default') {
            $connection = Config::get('database.default');
        }

        if (!Config::has('database.connections.' . $connection)) {
            throw new \Exception('Could not find connection `' . $connection . '` in your config.');
        }

        return $connection;
    }

    public function getPath($driver)
    {
        $basePath = $this->option('path');
        if ($basePath === 'default') {
            $basePath = ConfigResolver::path($driver);
        }

        return $basePath;
    }

    public function handle()
    {
        try {
            $connection = $this->getConnection();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
        $driver = $this->getValidDriver($connection);
        if ($driver === false) {
            $this->error('The `' . $connection . '` connection is not supported at this time.');
            return 1;
        }
        $this->info('Using connection ' . $connection);
        DB::setDefaultConnection($connection);




        $manager = $this->resolveGeneratorManager($driver);
        if ($manager === false) {
            $this->error('The `' . $driver . '` driver is not supported at this time.');

            return 1;
        }

        $basePath = base_path($this->getPath($driver));

        if ($this->option('empty-path') || config('advanced-migration-generator.clear_output_path')) {
            foreach (glob($basePath . '/*.php') as $file) {
                unlink($file);
            }
        }

        $this->info('Using ' . $basePath . ' as the output path..');
        $tableNames = [];
        if ($this->option('table')) {
            $tableNames = explode(',', $this->option('table'));
        }

        $viewNames = Arr::wrap($this->option('view'));
        $manager->setCommand($this);
        $manager->handle($basePath, $tableNames, $viewNames);
    }

    /**
     * @param string $driver
     * @return false|GeneratorManagerInterface
     */
    protected function resolveGeneratorManager(string $driver)
    {
        $supported = [
            Constants::MYSQL_DRIVER => MySQLGeneratorManager::class,
            Constants::SQLITE_DRIVER => SQLiteGeneratorManager::class,
            Constants::PGSQL_DRIVER => PostgresGeneratorManager::class,
            Constants::SQLSRV_DRIVER => SqlServerGeneratorManager::class,
        ];
        if (!isset($supported[$driver])) {
            return false;
        }
        return new $supported[$driver]();
    }
    /**
     * @param string $driver
     * @return false|string
     */
    protected function getValidDriver(string $connection)
    {
        try {
            $conn = Config::get('database.connections.' . $connection);

            if (!is_null($conn) && is_array($conn)) {
                $driver =  $conn['driver'];
                return $driver;
            }
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
        }
        return false;
    }
}
