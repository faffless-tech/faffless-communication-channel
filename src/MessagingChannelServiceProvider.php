<?php

namespace Rpos\MessagingChannel;

use Illuminate\Support\ServiceProvider;
use Rpos\MessagingChannel\Commands\InstallMessagingChannelCommand;
use Rpos\MessagingChannel\Mail\MessagingHubTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MessagingChannelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messaging-channel.php', 'messaging-channel');
    }

    public function boot(): void
    {
        $this->app['mail.manager']->extend('messaging_hub', function (array $config = []): TransportInterface {
            return new MessagingHubTransport(
                http: $this->app['http'],
                url: $config['url'] ?? config('messaging-channel.url'),
                appId: $config['app_id'] ?? config('messaging-channel.app_id'),
                secret: $config['secret'] ?? config('messaging-channel.secret'),
                timeout: (int) ($config['timeout'] ?? config('messaging-channel.timeout', 10)),
            );
        });

        $this->publishes([
            __DIR__.'/../config/messaging-channel.php' => config_path('messaging-channel.php'),
        ], 'messaging-channel-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallMessagingChannelCommand::class,
            ]);
        }
    }
}
