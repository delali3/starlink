<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:send')->dailyAt('08:00');
Schedule::command('subscriptions:expire')->daily();
