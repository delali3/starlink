<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::withCount(['users', 'regularUsers', 'admins'])
            ->with(['regularUsers' => function($query) {
                $query->withSum(['payments as revenue' => fn($q) => $q->where('status', 'success')], 'amount');
            }])
            ->latest()
            ->paginate(20);

        // Calculate revenue for each organization
        foreach ($organizations as $org) {
            $org->total_revenue = Payment::where('status', 'success')
                ->whereHas('user', fn($q) => $q->where('organization_id', $org->id))
                ->sum('amount');
        }

        return view('organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('organizations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['nullable', 'required_with:admin_email', 'string', 'max:255'],
            'admin_email' => ['nullable', 'required_with:admin_name', 'email', 'unique:users,email'],
            'admin_phone' => ['nullable', 'required_with:admin_email', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:users,phone'],
            'admin_password' => ['nullable', 'required_with:admin_email', 'string', 'min:8'],
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $organization = DB::transaction(function () use ($request, $slug) {
            $organization = Organization::create([
                'name' => $request->name,
                'slug' => $slug,
                'domain' => $request->domain,
                'status' => $request->input('status', 'active'),
            ]);

            // Create org admin if details provided
            if ($request->filled('admin_email')) {
                $admin = User::create([
                    'name' => $request->admin_name,
                    'email' => $request->admin_email,
                    'phone' => $request->admin_phone,
                    'password' => bcrypt($request->admin_password),
                    'status' => 'active',
                    'organization_id' => $organization->id,
                ]);
                $admin->assignRole('admin');
            }

            return $organization;
        });

        AuditLog::log('organization_created', auth()->user(), [
            'organization_id' => $organization->id,
            'name' => $organization->name,
        ]);

        return redirect()->route('organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization)
    {
        $organization->loadCount(['users', 'regularUsers', 'admins']);

        $users = User::where('organization_id', $organization->id)
            ->latest()
            ->paginate(20);

        // Admins not assigned to any org (available for assignment)
        $unassignedAdmins = User::role('admin')
            ->whereNull('organization_id')
            ->get();

        // Revenue statistics for this organization
        $paymentQuery = Payment::where('status', 'success')
            ->whereHas('user', fn($q) => $q->where('organization_id', $organization->id));

        $revenueToday = (clone $paymentQuery)->whereDate('paid_at', today())->sum('amount');
        $revenueThisMonth = (clone $paymentQuery)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
        $revenueTotal = (clone $paymentQuery)->sum('amount');

        // Service charges
        $serviceChargeTotal = (clone $paymentQuery)->sum('service_charge');
        $baseRevenueTotal = $revenueTotal - $serviceChargeTotal;

        // Users with payments (top payers)
        $usersWithPayments = User::where('organization_id', $organization->id)
            ->role('user')
            ->whereHas('payments', fn($q) => $q->where('status', 'success'))
            ->withSum(['payments as total_paid' => fn($q) => $q->where('status', 'success')], 'base_amount')
            ->orderBy('total_paid', 'desc')
            ->limit(10)
            ->get();

        // Users without any payments
        $usersWithoutPayments = User::where('organization_id', $organization->id)
            ->role('user')
            ->whereDoesntHave('payments', fn($q) => $q->where('status', 'success'))
            ->count();

        // Recent payments in this org
        $recentPayments = Payment::with('user')
            ->where('status', 'success')
            ->whereHas('user', fn($q) => $q->where('organization_id', $organization->id))
            ->latest('paid_at')
            ->limit(10)
            ->get();

        return view('organizations.show', compact(
            'organization',
            'users',
            'unassignedAdmins',
            'revenueToday',
            'revenueThisMonth',
            'revenueTotal',
            'serviceChargeTotal',
            'baseRevenueTotal',
            'usersWithPayments',
            'usersWithoutPayments',
            'recentPayments'
        ));
    }

    public function edit(Organization $organization)
    {
        return view('organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
        ]);

        $organization->update([
            'name' => $request->name,
            'domain' => $request->domain,
        ]);

        AuditLog::log('organization_updated', auth()->user(), [
            'organization_id' => $organization->id,
        ]);

        return redirect()->route('organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    public function suspend(Organization $organization)
    {
        $organization->update(['status' => 'suspended']);

        AuditLog::log('organization_suspended', auth()->user(), [
            'organization_id' => $organization->id,
            'name' => $organization->name,
        ]);

        return back()->with('success', 'Organization suspended successfully.');
    }

    public function activate(Organization $organization)
    {
        $organization->update(['status' => 'active']);

        AuditLog::log('organization_activated', auth()->user(), [
            'organization_id' => $organization->id,
            'name' => $organization->name,
        ]);

        return back()->with('success', 'Organization activated successfully.');
    }

    public function assignAdmin(Request $request, Organization $organization)
    {
        $request->validate([
            'admin_id' => ['required', 'exists:users,id'],
        ]);

        $admin = User::findOrFail($request->admin_id);

        if (!$admin->hasRole('admin')) {
            return back()->with('error', 'Selected user is not an admin.');
        }

        $admin->update(['organization_id' => $organization->id]);

        AuditLog::log('admin_assigned_to_org', auth()->user(), [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
        ]);

        return back()->with('success', "Admin {$admin->name} assigned to {$organization->name}.");
    }

    public function switchOrg(Request $request)
    {
        $request->validate([
            'organization_id' => ['nullable', 'exists:organizations,id'],
        ]);

        if ($request->organization_id) {
            session(['admin_org_filter' => $request->organization_id]);
        } else {
            session()->forget('admin_org_filter');
        }

        return back()->with('success', 'Organization filter updated.');
    }
}
