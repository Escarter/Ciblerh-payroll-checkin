<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\OrangeCameroonSMS;
use Illuminate\Console\Command;

class OrangeDiagCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orange:diag
                            {--phone= : E.164 phone number to send test SMS to, e.g. +2376XXXXXXXX}
                            {--message=Orange diagnostic test from CibleRH : Test SMS body}
                            {--dry-run : Only validate OAuth and MSP auth, do not send SMS}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose Orange Cameroon OAuth, Messaging Pro auth, and optional SMS send';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $setting = Setting::query()->where('company_id', 1)->first();
        if (! $setting || $setting->sms_provider !== 'orange_cm') {
            $this->error('SMS provider is not configured as orange_cm for company_id=1.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $phone = trim((string) $this->option('phone'));
        $message = (string) $this->option('message');

        if (! $dryRun && $phone === '') {
            $this->error('Option --phone is required when not using --dry-run.');

            return self::FAILURE;
        }

        $this->line('Running Orange diagnostics...');
        $this->line('Mode: '.($dryRun ? 'dry-run (no SMS send)' : 'send test SMS'));

        $client = new OrangeCameroonSMS($setting);
        $result = $client->runDiagnostics($phone, $message, $dryRun);

        $rows = [
            ['OAuth token (api.orange.com)', ($result['oauth']['ok'] ?? false) ? 'OK' : 'FAILED', $result['oauth']['error'] ?? ''],
            ['MSP auth (api.orange.cm)', ($result['msp']['ok'] ?? false) ? 'OK' : 'FAILED', $result['msp']['error'] ?? ''],
        ];
        if (! $dryRun) {
            $send = $result['send'] ?? [];
            $rows[] = ['Send SMS (/messaging/v1/sms/simple)', ($send['ok'] ?? false) ? 'OK' : 'FAILED', $send['error'] ?? ''];
        }

        $this->table(['Step', 'Status', 'Detail'], $rows);

        $hasFailure = ! ($result['oauth']['ok'] ?? false) || ! ($result['msp']['ok'] ?? false) || (! $dryRun && ! (($result['send']['ok'] ?? false)));
        if ($hasFailure) {
            $this->warn('One or more checks failed. Review the Detail column for the failing step.');

            return self::FAILURE;
        }

        $this->info('All requested Orange diagnostics checks passed.');

        return self::SUCCESS;
    }
}
