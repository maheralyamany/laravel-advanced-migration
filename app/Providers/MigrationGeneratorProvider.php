<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\MigrationsEnded;
use App\Commands\GenerateMigrationsCommand;

class MigrationGeneratorProvider extends ServiceProvider
{
    public function boot()
    {

        $this->mergeConfigFrom(
           config_path('migration-generator.php'),
            'migration-generator'
        );



        if ($this->app->runningInConsole()) {
            $this->app->instance('migration-generator:time', now());
            $this->commands([
                GenerateMigrationsCommand::class
            ]);
        }
        if (config('migration-generator.run_after_migrations') && config('app.env') === 'local') {
            Event::listen(MigrationsEnded::class, function () {
                Artisan::call('generate:migrations');
            });
        }
    }
}
