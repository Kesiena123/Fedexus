<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationDispatchService
{
    public function send(User $user, string $title, string $body, array $data = [], array $channels = ['in_app'], array $destinations = []): array
    {
        $statuses = [];
        $preferences = $user->id
            ? NotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                ['email' => true, 'in_app' => true, 'sms' => false, 'marketing' => false]
            )
            : null;

        foreach ($channels as $channel) {
            if ($channel === 'in_app') {
                if ($preferences && !$preferences->in_app) {
                    $statuses['in_app'] = 'skipped:optout';
                    continue;
                }

                if (!$user->id) {
                    $statuses['in_app'] = 'skipped:no_user';
                    continue;
                }

                InAppNotification::create([
                    'user_id' => $user->id,
                    'type' => $data['type'] ?? 'system',
                    'channel' => 'in_app',
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ]);

                $statuses['in_app'] = 'stored';

                continue;
            }

            if ($channel === 'email') {
                if ($preferences && !$preferences->email) {
                    $statuses['email'] = 'skipped:optout';
                    continue;
                }

                $recipientEmail = $destinations['email'] ?? $user->email;

                if (! $recipientEmail) {
                    $statuses['email'] = 'skipped:no_recipient';

                    continue;
                }

                $status = 'sent';

                try {
                    Mail::raw($body, static function ($message) use ($recipientEmail, $title): void {
                        $message->to($recipientEmail)
                            ->subject($title)
                            ->from(
                                config('mail.from.address'),
                                config('mail.from.name', config('app.name'))
                            );
                    });
                } catch (Throwable $exception) {
                    $status = 'logged_fallback';
                    Log::warning('Email notification delivery failed, falling back to log output.', [
                        'recipient' => $recipientEmail,
                        'title' => $title,
                        'error' => $exception->getMessage(),
                    ]);
                    Log::info('Email notification payload', [
                        'recipient' => $recipientEmail,
                        'title' => $title,
                        'body' => $body,
                        'data' => $data,
                    ]);
                }

                if ($user->id) {
                    InAppNotification::create([
                        'user_id' => $user->id,
                        'type' => $data['type'] ?? 'system',
                        'channel' => 'email',
                        'title' => $title,
                        'body' => $body,
                        'data' => array_merge($data, [
                            'recipient' => $recipientEmail,
                            'delivery_status' => $status,
                        ]),
                    ]);
                }

                $statuses['email'] = $status;

                continue;
            }

            if ($channel === 'sms') {
                if ($preferences && !$preferences->sms) {
                    $statuses['sms'] = 'skipped:optout';
                    continue;
                }

                $recipientPhone = $destinations['sms'] ?? $user->phone;

                if (! $recipientPhone) {
                    $statuses['sms'] = 'skipped:no_recipient';

                    continue;
                }

                Log::info('SMS notification payload', [
                    'recipient' => $recipientPhone,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ]);

                if ($user->id) {
                    InAppNotification::create([
                        'user_id' => $user->id,
                        'type' => $data['type'] ?? 'system',
                        'channel' => 'sms',
                        'title' => $title,
                        'body' => $body,
                        'data' => array_merge($data, [
                            'recipient' => $recipientPhone,
                            'delivery_status' => 'logged_for_dispatch',
                        ]),
                    ]);
                }

                $statuses['sms'] = 'logged_for_dispatch';
            }
        }

        return $statuses;
    }
}
