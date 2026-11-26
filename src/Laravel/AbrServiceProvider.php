<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Laravel;

use AthenaNetworks\Abr\AbrClient;
use AthenaNetworks\Abr\Contracts\AbrClient as AbrClientContract;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

final class AbrServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/abr.php', 'abr');

        $this->app->singleton(AbrClientContract::class, static function (Application $app): AbrClient {
            /** @var Repository $config */
            $config = $app->make('config');

            return new AbrClient(
                guid: (string) $config->get('abr.guid', ''),
                endpoint: (string) $config->get('abr.endpoint', AbrClient::DEFAULT_ENDPOINT),
            );
        });

        $this->app->alias(AbrClientContract::class, AbrClient::class);
        $this->app->alias(AbrClientContract::class, 'abr');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/abr.php' => $this->app->configPath('abr.php'),
            ], 'abr-config');
        }
    }

    /** @return list<string> */
    public function provides(): array
    {
        return [AbrClientContract::class, AbrClient::class, 'abr'];
    }
}
