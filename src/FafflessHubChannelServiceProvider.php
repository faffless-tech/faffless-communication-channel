<?php

namespace Faffless\CommunicationChannel;

use Illuminate\Support\ServiceProvider;
use Faffless\CommunicationChannel\Commands\InstallFafflessHubChannelCommand;
use Faffless\CommunicationChannel\Mail\FafflessHubTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class FafflessHubChannelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/faffless-hub.php', 'faffless-hub');
    }

    public function boot(): void
    {
        $this->app['mail.manager']->extend('faffless', function (array $config = []): TransportInterface {
            return new FafflessHubTransport(
                http: $this->app['http'],
                url: $config['url'] ?? config('faffless-hub.url'),
                appId: $config['app_id'] ?? config('faffless-hub.app_id'),
                secret: $config['secret'] ?? config('faffless-hub.secret'),
                timeout: (int) ($config['timeout'] ?? config('faffless-hub.timeout', 10)),
            );
        });

        $this->publishes([
            __DIR__.'/../config/faffless-hub.php' => config_path('faffless-hub.php'),
        ], 'faffless-hub-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallFafflessHubChannelCommand::class,
            ]);
        }
    }
}
