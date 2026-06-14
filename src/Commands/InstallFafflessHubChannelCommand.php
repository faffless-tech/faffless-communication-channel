<?php

namespace Faffless\CommunicationChannel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallFafflessHubChannelCommand extends Command
{
    protected $signature = 'faffless-channel:install
        {--url= : Communication Hub service base URL}
        {--api-key= : Issued Faffless Hub API key}
        {--force : Overwrite the published config file if it exists}';

    protected $description = 'Publish Faffless Hub channel config and add required environment keys.';

    public function handle(): int
    {
        $this->addMailConfiguration();

        $this->call('vendor:publish', [
            '--tag' => 'faffless-hub-config',
            '--force' => $this->option('force'),
        ]);

        $keys = [
            'FAFFLESS_HUB_API_KEY' => $this->option('api-key') ?: '',
        ];

        if ($this->option('url')) {
            $keys['FAFFLESS_HUB_URL'] = $this->option('url');
        }

        $this->appendEnvKeys($keys);

        $this->components->info('Faffless communication hub channel installed.');
        $this->line('Set MAIL_MAILER=faffless to route Laravel mail through Faffless.');
        $this->line('Use Faffless\\CommunicationChannel\\FafflessHubChannel for explicit non-mail hub notifications.');

        return self::SUCCESS;
    }

    private function addMailConfiguration(): void
    {
        $path = config_path('mail.php');

        if (! File::exists($path)) {
            return;
        }

        $contents = File::get($path);

        if (str_contains($contents, "'faffless' => [")) {
            return;
        }

        $mailer = <<<'PHP'
        'faffless' => [
            'transport' => 'faffless',
            'url' => env('FAFFLESS_HUB_URL', 'https://communication.faffless.ai'),
            'api_key' => env('FAFFLESS_HUB_API_KEY'),
            'timeout' => 10,
        ],

PHP;

        $updated = preg_replace(
            "/('mailers'\s*=>\s*\[\R)/",
            "$1".$mailer,
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

        File::append($path, PHP_EOL.'# Faffless Communication'.PHP_EOL.implode(PHP_EOL, $lines).PHP_EOL);
    }
}
