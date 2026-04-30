<?php

namespace Rpos\MessagingChannel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallMessagingChannelCommand extends Command
{
    protected $signature = 'messaging-channel:install
        {--url= : Messaging service base URL}
        {--app-id= : Issued messaging application id}
        {--secret= : Issued messaging application secret. Prefer the hidden prompt instead of this option.}
        {--force : Overwrite the published config file if it exists}';

    protected $description = 'Publish messaging channel config and add required environment keys.';

    public function handle(): int
    {
        $this->addMailConfiguration();

        $this->call('vendor:publish', [
            '--tag' => 'messaging-channel-config',
            '--force' => $this->option('force'),
        ]);

        $this->appendEnvKeys([
            'MESSAGING_SERVICE_URL' => $this->option('url') ?: 'https://messaging.example.com',
            'MESSAGING_APP_ID' => $this->option('app-id') ?: '',
            'MESSAGING_APP_SECRET' => $this->option('secret') ?: $this->secret('Messaging app secret') ?: '',
            'MESSAGING_SERVICE_TIMEOUT' => '10',
            'MESSAGING_BRAND_KEY' => '${VITE_BRAND_KEY}',
            'MESSAGING_BRAND_NAME' => '${VITE_BRAND_NAME}',
            'MESSAGING_BRAND_REGION' => '${VITE_BRAND_REGION}',
        ]);

        $this->components->info('RPOS messaging channel installed.');
        $this->line('Set MAIL_MAILER=messaging_hub to route Laravel mail through the messaging hub.');
        $this->line('Use Rpos\\MessagingChannel\\MessagingChannel for explicit non-mail hub notifications.');

        return self::SUCCESS;
    }

    private function addMailConfiguration(): void
    {
        $path = config_path('mail.php');

        if (! File::exists($path)) {
            return;
        }

        $contents = File::get($path);

        if (str_contains($contents, "'messaging_hub' => [")) {
            return;
        }

        $mailer = <<<'PHP'

        'messaging_hub' => [
            'transport' => 'messaging_hub',
            'url' => env('MESSAGING_SERVICE_URL'),
            'app_id' => env('MESSAGING_APP_ID'),
            'secret' => env('MESSAGING_APP_SECRET'),
            'timeout' => env('MESSAGING_SERVICE_TIMEOUT', 10),
            'brand_key' => env('MESSAGING_BRAND_KEY', env('VITE_BRAND_KEY')),
            'brand_name' => env('MESSAGING_BRAND_NAME', env('VITE_BRAND_NAME')),
            'brand_region' => env('MESSAGING_BRAND_REGION', env('VITE_BRAND_REGION')),
        ],
PHP;

        $updated = preg_replace(
            "/('mailers'\s*=>\s*\[\s*)/",
            "$1".$mailer.PHP_EOL,
            $contents,
            1
        );

        if ($updated && $updated !== $contents) {
            File::put($path, $updated);
        }
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
