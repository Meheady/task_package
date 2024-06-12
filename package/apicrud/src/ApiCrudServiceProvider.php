<?php


namespace Package\Apicrud;

use Illuminate\Support\ServiceProvider;
use Package\Apicrud\Console\CrudCommand;

class ApiCrudServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        //$this->loadRoutesFrom(__DIR__.'/routes/web.php');

        $this->commands([
            CrudCommand::class,
        ]);

        $this->publishes([
            __DIR__ . '/stubs' => base_path('resources/crud/stubs'),
        ], 'crud-stubs');
    }
}
