<?php

namespace App\Providers;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $this->applyMailConfig();
        } catch (\Throwable $e) {
            Log::warning('MailConfigServiceProvider: could not load DB mail settings', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function applyMailConfig(): void
    {
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            return;
        }

        $smtpHost = AdminSetting::value('smtp_host');
        if (empty($smtpHost)) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => $smtpHost,
                'port' => (int) AdminSetting::value('smtp_port', 587),
                'username' => AdminSetting::value('smtp_username', ''),
                'password' => AdminSetting::value('smtp_password', ''),
                'encryption' => AdminSetting::value('smtp_encryption', 'tls'),
                'timeout' => null,
                'local_domain' => null,
            ],
            'mail.from' => [
                'address' => AdminSetting::value('mail_from_address', config('mail.from.address')),
                'name' => AdminSetting::value('mail_from_name', config('app.name')),
            ],
        ]);
    }
}
