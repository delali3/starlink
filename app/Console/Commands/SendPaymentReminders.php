<?php

namespace App\Console\Commands;

use App\Jobs\SendSmsJob;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send SMS reminders for subscriptions expiring tomorrow';

    public function handle(): int
    {
        $expiringSubscriptions = Subscription::where('status', 'active')
            ->whereDate('end_date', now()->addDay()->toDateString())
            ->whereNull('last_reminder_sent_at')
            ->with('user')
            ->get();

        $messageTemplate = Setting::get(
            'reminder_sms_message',
            'Hi {name}, your GhLinks subscription expires tomorrow. Please renew at starlink.ghprofit.com'
        );

        foreach ($expiringSubscriptions as $subscription) {
            $message = str_replace('{name}', $subscription->user->name, $messageTemplate);
            SendSmsJob::dispatch($subscription->user->phone, $message);
            $subscription->update(['last_reminder_sent_at' => now()]);
        }

        $this->info("Sent {$expiringSubscriptions->count()} reminders.");
        return Command::SUCCESS;
    }
}
