<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's payments.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user's audit logs.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the user's active subscription.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest();
    }

    /**
     * Check if user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Check if user is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Suspend the user.
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Activate the user.
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Get total amount paid by user (successful payments only).
     */
    public function getTotalPaid(): float
    {
        return (float) $this->payments()
            ->where('status', 'success')
            ->sum('amount');
    }

    /**
     * Get expected amount based on days since registration.
     * Assuming GHC 3 per day.
     * Registration day counts as day 1.
     * Weekends (Saturday & Sunday) are excluded.
     */
    public function getExpectedAmount(): float
    {
        $startDate = $this->created_at->startOfDay();
        $endDate = now()->startOfDay();
        
        // Count only weekdays (Monday-Friday)
        $weekdaysCount = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            // isWeekday() returns true for Monday-Friday
            if ($currentDate->isWeekday()) {
                $weekdaysCount++;
            }
            $currentDate->addDay();
        }
        
        $dailyRate = config('services.payment.daily_price', 3);
        
        return $weekdaysCount * $dailyRate;
    }

    /**
     * Get remaining balance (negative means user owes money).
     */
    public function getBalance(): float
    {
        return $this->getTotalPaid() - $this->getExpectedAmount();
    }

    /**
     * Check if user's balance is positive (has credit).
     */
    public function hasCredit(): bool
    {
        return $this->getBalance() > 0;
    }

    /**
     * Get the amount user owes (if balance is negative).
     */
    public function getAmountOwed(): float
    {
        $balance = $this->getBalance();
        return $balance < 0 ? abs($balance) : 0;
    }

    /**
     * Get number of days until balance runs out.
     */
    public function getDaysRemaining(): int
    {
        $balance = $this->getBalance();
        
        if ($balance <= 0) {
            return 0;
        }
        
        $dailyRate = config('services.payment.daily_price', 3);
        return (int) floor($balance / $dailyRate);
    }

    /**
     * Get the actual date when credit runs out (accounting for weekends).
     */
    public function getCreditExpiryDate(): ?\Carbon\Carbon
    {
        $daysRemaining = $this->getDaysRemaining();
        
        if ($daysRemaining <= 0) {
            return null;
        }
        
        $currentDate = now()->startOfDay();
        $weekdaysAdded = 0;
        
        // Add weekdays until we've added enough days
        while ($weekdaysAdded < $daysRemaining) {
            $currentDate->addDay();
            if ($currentDate->isWeekday()) {
                $weekdaysAdded++;
            }
        }
        
        return $currentDate;
    }
}
