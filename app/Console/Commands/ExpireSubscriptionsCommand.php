<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire subscriptions that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired subscriptions...');

        $expiredCount = Subscription::where('status', 'active')
            ->where('end_date', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$expiredCount} subscription(s).");

        return Command::SUCCESS;
    }
}
