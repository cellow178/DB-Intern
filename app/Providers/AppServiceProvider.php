<?php

namespace App\Providers;

use App\CoreService\CallService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        setlocale(LC_ALL, 'IND');
        date_default_timezone_set('Asia/Jakarta');
        Validator::extend('exists_file', function ($attribute, $value, $parameters, $validator) {
            return Storage::exists($value);
        });

        Route::middleware(['web', 'setguard:web'])->group(function () {

            foreach (Config::get("service") as $route) {
                $reflect = new \ReflectionClass($route["class"]);
                $serviceName = $reflect->getShortName();
                $this->app->singleton($serviceName, $route["class"]);
                if (!isset($route["end_point"]) && !isset($route["type"])) continue;

                if ($route["type"] == "POST") {
                    Route::post($route["end_point"], function () use ($serviceName) {
                        $input = request()->all();
                        return CallService::execute($serviceName, $input);
                    });
                } else if ($route["type"] == "GET") {
                    Route::get($route["end_point"], function () use ($serviceName) {
                        $input = request()->all();
                        return CallService::execute($serviceName, $input);
                    });
                } else if ($route["type"] == "PUT") {
                    Route::put($route["end_point"], function ($id) use ($serviceName) {
                        $input = request()->all();
                        $input['id'] = $id;
                        return CallService::execute($serviceName, $input);
                    });
                } else if ($route["type"] == "DELETE") {
                    Route::delete($route["end_point"], function ($id) use ($serviceName) {
                        $input = request()->all();
                        $input['id'] = $id;
                        return CallService::execute($serviceName, $input);
                    });
                }
            };
        });

        Route::prefix("api")->middleware(['api', 'setguard:api'])->group(function () {

            foreach (Config::get("service") as $route) {
                $reflect = new \ReflectionClass($route["class"]);
                $serviceName = $reflect->getShortName();
                $this->app->singleton($serviceName, $route["class"]);
                if (!isset($route["end_point"]) && !isset($route["type"])) continue;

                if ($route["type"] == "POST") {
                    Route::post($route["end_point"], function () use ($serviceName) {
                        $input = request()->all();
                        return CallService::execute($serviceName, $input);
                    });
                } else if ($route["type"] == "GET") {
                    Route::get($route["end_point"], function () use ($serviceName) {
                        $input = request()->all();
                        return CallService::execute($serviceName, $input);
                    });
                } else if ($route["type"] == "SHOW") {
                    Route::get($route["end_point"], function ($id) use ($serviceName) {
                        $input = request()->all();
                        $input['id'] = $id;
                        return CallService::execute($serviceName, $input);
                    });
                } else if ($route["type"] == "PUT") {
                    Route::put($route["end_point"], function ($id) use ($serviceName) {
                        $input = request()->all();
                        $input['id'] = $id;
                        return CallService::execute($serviceName, $input);
                    });
                } else if ($route["type"] == "DELETE") {
                    Route::delete($route["end_point"], function ($id) use ($serviceName) {
                        $input = request()->all();
                        $input['id'] = $id;
                        return CallService::execute($serviceName, $input);
                    });
                }
            };
        });
        require __DIR__ . "../../helpers/globalFunction.php";
    }
}
