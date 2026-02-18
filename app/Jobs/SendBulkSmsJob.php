<?php

namespace App\Jobs;

use App\Models\SmsNotification;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        public int $smsNotificationId
    ) {}

    public function handle(SmsService $smsService): void
    {
        $notification = SmsNotification::find($this->smsNotificationId);
        if (!$notification) return;

        $notification->update(['status' => 'sending']);

        $query = User::role('user');
        $filters = $notification->filters ?? [];

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['subscription_status']) && $filters['subscription_status'] === 'unpaid') {
            $query->whereDoesntHave('subscriptions', function ($q) {
                $q->where('status', 'active')->where('end_date', '>=', now());
            });
        }
        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        $sentCount = 0;
        $failedCount = 0;

        $query->chunk(100, function ($users) use ($smsService, $notification, &$sentCount, &$failedCount) {
            foreach ($users as $user) {
                $message = str_replace('{name}', $user->name, $notification->message);
                if ($smsService->send($user->phone, $message)) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }

            $notification->update([
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);
        });

        $notification->update([
            'status' => 'completed',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);
    }
}
