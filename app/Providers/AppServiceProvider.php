<?php

namespace App\Providers;

use App\Models\VaultFolder;
use App\Models\VaultItem;
use App\Policies\VaultFolderPolicy;
use App\Policies\VaultItemPolicy;
use App\Services\Socialite\HiveProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

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
    public function boot(): void
    {
        Gate::policy(VaultFolder::class, VaultFolderPolicy::class);
        Gate::policy(VaultItem::class, VaultItemPolicy::class);

        Socialite::extend('hive', function (Application $app): HiveProvider {
            $config = config('services.hive');
            $verify = filter_var(config('services.hive.verify_ssl', true), FILTER_VALIDATE_BOOLEAN);

            if ($verify && filled($config['ca_bundle'])) {
                $verify = $config['ca_bundle'];
            }

            return new HiveProvider(
                $app['request'],
                $config['client_id'],
                $config['client_secret'],
                $config['redirect'],
                $config['base_url'],
                [
                    'verify' => $verify,
                ],
            );
        });
    }
}
