<?php

namespace Rpos\MessagingChannel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallMessagingChannelCommand extends Command
{
    protected $signature = 'messaging-channel:install
        {--url= : Messaging service base URL}
        {--app-id= : Issued messaging application id}
        {--secret= : Issued messaging application secret}
        {--force : Overwrite the published config file if it exists}';

    protected $description = 'Publish messaging channel config and add required environment keys.';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'messaging-channel-config',
            '--force' => $this->option('force'),
        ]);

        $this->appendEnvKeys([
            'MESSAGING_SERVICE_URL' => $this->option('url') ?: 'https://messaging.example.com',
            'MESSAGING_APP_ID' => $this->option('app-id') ?: '',
            'MESSAGING_APP_SECRET' => $this->option('secret') ?: '',
            'MESSAGING_SERVICE_TIMEOUT' => '10',
        ]);

        $this->components->info('RPOS messaging channel installed.');
        $this->line('Use Rpos\\MessagingChannel\\MessagingChannel in your notification via() method.');

        return self::SUCCESS;
    }

    private function appendEnvKeys(array $keys): void
    {
        $path = base_path('.env');

        if (! File::exists($path)) {
            File::put($path, '');
        }

        $contents = File::get($path);
        $lines = [];

        foreach ($keys as $key => $value) {
            if (preg_match("/^{$key}=/m", $contents)) {
                continue;
            }

            $lines[] = $key.'='.$value;
        }

        if ($lines === []) {
            return;
        }

        File::append($path, PHP_EOL.'# RPOS Messaging'.PHP_EOL.implode(PHP_EOL, $lines).PHP_EOL);
    }
}
