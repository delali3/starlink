<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the dashboard based on user role.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('superadmin')) {
            return $this->superAdminDashboard();
        }

        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }

        return $this->userDashboard();
    }

    /**
     * SuperAdmin Dashboard.
     */
    private function superAdminDashboard()
    {
        $orgFilter = session('admin_org_filter');

        // Base payment query with optional org filter
        $paymentQuery = Payment::where('status', 'success');
        if ($orgFilter) {
            $paymentQuery->whereHas('user', fn($q) => $q->where('organization_id', $orgFilter));
        }

        // Revenue statistics (including service charges for admin view)
        $revenueToday = (clone $paymentQuery)->whereDate('paid_at', today())->sum('amount');
        $revenueThisMonth = (clone $paymentQuery)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
        $revenueTotal = (clone $paymentQuery)->sum('amount');

        // Service charge breakdown
        $serviceChargeToday = (clone $paymentQuery)->whereDate('paid_at', today())->sum('service_charge');
        $serviceChargeThisMonth = (clone $paymentQuery)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('service_charge');
        $serviceChargeTotal = (clone $paymentQuery)->sum('service_charge');

        // Base revenue (what customers paid)
        $baseRevenueToday = $revenueToday - $serviceChargeToday;
        $baseRevenueThisMonth = $revenueThisMonth - $serviceChargeThisMonth;
        $baseRevenueTotal = $revenueTotal - $serviceChargeTotal;

        // Subscription statistics
        $subscriptionQuery = Subscription::query();
        if ($orgFilter) {
            $subscriptionQuery->whereHas('user', fn($q) => $q->where('organization_id', $orgFilter));
        }

        $activeSubscriptions = (clone $subscriptionQuery)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->count();

        $expiredSubscriptions = (clone $subscriptionQuery)
            ->where(function ($q) {
                $q->where('status', 'expired')->orWhere('end_date', '<', now());
            })
            ->count();

        // User statistics
        $userQuery = User::role('user');
        if ($orgFilter) {
            $userQuery->where('organization_id', $orgFilter);
        }

        $totalUsers = (clone $userQuery)->count();
        $activeUsers = (clone $userQuery)->where('status', 'active')->count();
        $suspendedUsers = (clone $userQuery)->where('status', 'suspended')->count();

        // Organization stats
        $totalOrganizations = Organization::count();
        $activeOrganizations = Organization::where('status', 'active')->count();

        // Recent payments
        $recentPaymentsQuery = Payment::with('user')->where('status', 'success');
        if ($orgFilter) {
            $recentPaymentsQuery->whereHas('user', fn($q) => $q->where('organization_id', $orgFilter));
        }
        $recentPayments = $recentPaymentsQuery->latest()->limit(10)->get();

        // Monthly revenue chart data (last 12 months)
        $monthlyRevenueQuery = Payment::where('status', 'success')
            ->where('paid_at', '>=', now()->subMonths(12));
        if ($orgFilter) {
            $monthlyRevenueQuery->whereHas('user', fn($q) => $q->where('organization_id', $orgFilter));
        }
        $monthlyRevenue = $monthlyRevenueQuery
            ->select(
                DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Monthly user registration data (last 12 months)
        $monthlyUsersQuery = User::role('user')
            ->where('created_at', '>=', now()->subMonths(12));
        if ($orgFilter) {
            $monthlyUsersQuery->where('organization_id', $orgFilter);
        }
        $monthlyUsers = $monthlyUsersQuery
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Organizations list for switcher with revenue data
        $organizations = Organization::orderBy('name')->get();
        foreach ($organizations as $org) {
            $org->total_revenue = Payment::where('status', 'success')
                ->whereHas('user', fn($q) => $q->where('organization_id', $org->id))
                ->sum('amount');
            $org->users_count = $org->users()->count();
        }
        $currentOrg = $orgFilter ? Organization::find($orgFilter) : null;

        return view('dashboard.superadmin', compact(
            'revenueToday',
            'revenueThisMonth',
            'revenueTotal',
            'serviceChargeToday',
            'serviceChargeThisMonth',
            'serviceChargeTotal',
            'baseRevenueToday',
            'baseRevenueThisMonth',
            'baseRevenueTotal',
            'activeSubscriptions',
            'expiredSubscriptions',
            'totalUsers',
            'activeUsers',
            'suspendedUsers',
            'totalOrganizations',
            'activeOrganizations',
            'recentPayments',
            'monthlyRevenue',
            'monthlyUsers',
            'organizations',
            'currentOrg'
        ));
    }

    /**
     * Admin Dashboard — scoped by organization.
     */
    private function adminDashboard()
    {
        $orgId = auth()->user()->organization_id;

        $totalUsers = User::role('user')->where('organization_id', $orgId)->count();
        $activeUsers = User::role('user')->where('organization_id', $orgId)->where('status', 'active')->count();

        // Users without active subscription
        $unpaidUsers = User::role('user')
            ->where('organization_id', $orgId)
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>=', now());
            })
            ->count();

        // Recent payments (scoped by org)
        $recentPayments = Payment::with('user')
            ->where('status', 'success')
            ->whereHas('user', fn($q) => $q->where('organization_id', $orgId))
            ->latest()
            ->limit(10)
            ->get();

        // Recent registrations
        $recentUsers = User::role('user')
            ->where('organization_id', $orgId)
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.admin', compact(
            'totalUsers',
            'activeUsers',
            'unpaidUsers',
            'recentPayments',
            'recentUsers'
        ));
    }

    /**
     * User Dashboard.
     */
    private function userDashboard()
    {
        $user = auth()->user();

        // Get active subscription
        $activeSubscription = $user->activeSubscription()->first();

        // Get payment history
        $payments = $user->payments()
            ->latest()
            ->paginate(10);

        // Subscription status
        $subscriptionStatus = [
            'is_active' => $activeSubscription && $activeSubscription->isActive(),
            'type' => $activeSubscription?->type,
            'end_date' => $activeSubscription?->end_date,
            'days_remaining' => $activeSubscription?->daysRemaining() ?? 0,
        ];

        // Balance tracking
        $balanceInfo = [
            'total_paid' => $user->getTotalPaid(),
            'expected_amount' => $user->getExpectedAmount(),
            'balance' => $user->getBalance(),
            'amount_owed' => $user->getAmountOwed(),
            'days_remaining' => $user->getDaysRemaining(),
            'has_credit' => $user->hasCredit(),
            'credit_expiry_date' => $user->getCreditExpiryDate(),
        ];

        // Current month info
        $currentMonthInfo = [
            'expected' => $user->getExpectedAmountForCurrentMonth(),
            'paid' => $user->getTotalPaidThisMonth(),
            'left_to_pay' => $user->getAmountLeftForCurrentMonth(),
        ];

        // Skipped months
        $skippedMonths = $user->getSkippedMonths();

        return view('dashboard.user', compact(
            'activeSubscription',
            'payments',
            'subscriptionStatus',
            'balanceInfo',
            'currentMonthInfo',
            'skippedMonths'
        ));
    }
}
