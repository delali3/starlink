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
        'organization_id',
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
     * Get the user's organization.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if user is a superadmin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    /**
     * Check if user is an org admin.
     */
    public function isOrgAdmin(): bool
    {
        return $this->hasRole('admin') && $this->organization_id !== null;
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
     * Uses base_amount (what user actually paid, excluding service charges).
     */
    public function getTotalPaid(): float
    {
        return (float) $this->payments()
            ->where('status', 'success')
            ->sum('base_amount');
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

    /**
     * Get expected amount for the current month (weekdays only).
     */
    public function getExpectedAmountForCurrentMonth(): float
    {
        $startOfMonth = now()->startOfMonth();
        $today = now()->startOfDay();
        
        // If user registered this month, start from registration date
        if ($this->created_at->isCurrentMonth()) {
            $startOfMonth = $this->created_at->startOfDay();
        }
        
        // Count weekdays in current month up to today
        $weekdaysCount = 0;
        $currentDate = $startOfMonth->copy();
        
        while ($currentDate <= $today) {
            if ($currentDate->isWeekday()) {
                $weekdaysCount++;
            }
            $currentDate->addDay();
        }
        
        $dailyRate = config('services.payment.daily_price', 3);
        return $weekdaysCount * $dailyRate;
    }

    /**
     * Get total payments made in the current month.
     * Uses base_amount (what user actually paid, excluding service charges).
     */
    public function getTotalPaidThisMonth(): float
    {
        return (float) $this->payments()
            ->where('status', 'success')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('base_amount');
    }

    /**
     * Get amount left to pay for current month.
     */
    public function getAmountLeftForCurrentMonth(): float
    {
        $expected = $this->getExpectedAmountForCurrentMonth();
        $paid = $this->getTotalPaidThisMonth();
        $leftToPay = $expected - $paid;
        
        return $leftToPay > 0 ? $leftToPay : 0;
    }

    /**
     * Get months user skipped payments (months where expected > 0 but payment = 0).
     */
    public function getSkippedMonths(): array
    {
        $registrationDate = $this->created_at;
        $today = now();
        $skippedMonths = [];
        
        $currentMonth = $registrationDate->copy()->startOfMonth();
        
        while ($currentMonth < $today->startOfMonth()) {
            // Calculate expected amount for this month
            $monthStart = $currentMonth->copy();
            $monthEnd = $currentMonth->copy()->endOfMonth();
            
            // Count weekdays in that month
            $weekdaysCount = 0;
            $date = $monthStart->copy();
            
            while ($date <= $monthEnd) {
                if ($date->isWeekday()) {
                    $weekdaysCount++;
                }
                $date->addDay();
            }
            
            $expectedAmount = $weekdaysCount * config('services.payment.daily_price', 3);
            
            // Get payments made in that month
            $paidAmount = $this->payments()
                ->where('status', 'success')
                ->whereYear('created_at', $currentMonth->year)
                ->whereMonth('created_at', $currentMonth->month)
                ->sum('amount');
            
            // If expected > 0 but paid = 0, it's a skipped month
            if ($expectedAmount > 0 && $paidAmount == 0) {
                $skippedMonths[] = [
                    'month' => $currentMonth->format('F Y'),
                    'expected' => $expectedAmount,
                    'paid' => 0,
                    'owed' => $expectedAmount,
                ];
            } elseif ($paidAmount < $expectedAmount) {
                $skippedMonths[] = [
                    'month' => $currentMonth->format('F Y'),
                    'expected' => $expectedAmount,
                    'paid' => $paidAmount,
                    'owed' => $expectedAmount - $paidAmount,
                ];
            }
            
            $currentMonth->addMonth();
        }
        
        return $skippedMonths;
    }
}
