<?php

namespace App\Providers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DatabasePasswordServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->booted(function () {
            if (env('DB_ENCRYPTION', false)) {
                $password = Crypt::decrypt(config('database.connections.pgsql.password'));
                config(['database.connections.pgsql.password' => $password]);

                $database = Crypt::decrypt(config('database.connections.pgsql.database'));
                config(['database.connections.pgsql.database' => $database]);

                $username = Crypt::decrypt(config('database.connections.pgsql.username'));
                config(['database.connections.pgsql.username' => $username]);

                $host = Crypt::decrypt(config('database.connections.pgsql.host'));
                config(['database.connections.pgsql.host' => $host]);

                $port = Crypt::decrypt(config('database.connections.pgsql.port'));
                config(['database.connections.pgsql.port' => $port]);
                
                DB::purge('pgsql');
            }
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
