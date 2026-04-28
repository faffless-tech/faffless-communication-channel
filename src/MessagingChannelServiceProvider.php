<?php

namespace Rpos\MessagingChannel;

use Illuminate\Support\ServiceProvider;
use Rpos\MessagingChannel\Commands\InstallMessagingChannelCommand;

class MessagingChannelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messaging-channel.php', 'messaging-channel');
    }

    public function boot(): void
    {
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
