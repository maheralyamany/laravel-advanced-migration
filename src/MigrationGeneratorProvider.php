<?php

namespace AdvancedMigration;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\MigrationsEnded;
use AdvancedMigration\Commands\GenerateMigrationsCommand;

class MigrationGeneratorProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/advanced-migration-generator.php', 'advanced-migration-generator');
        $this->publishes([
            __DIR__ . '/../stubs'                                  => resource_path('stubs/vendor/advanced-migration-generator'),
            __DIR__ . '/../config/advanced-migration-generator.php' => config_path('advanced-migration-generator.php'),
        ]);
        if ($this->app->runningInConsole()) {
            $this->app->instance('advanced-migration-generator:time', now());
            $this->commands([
                GenerateMigrationsCommand::class
            ]);
        }
        if (config('advanced-migration-generator.run_after_migrations') && config('app.env') === 'local') {
            Event::listen(MigrationsEnded::class, function () {
                Artisan::call('generate:migrations');
            });
        }
    }
}
