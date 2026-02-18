<?php

namespace App\Http\Controllers;

use App\Jobs\SendSmsJob;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    /**
     * Scope user query by organization.
     */
    private function scopeByOrg($query)
    {
        $user = auth()->user();

        if ($user->hasRole('superadmin')) {
            if ($orgId = session('admin_org_filter')) {
                $query->where('organization_id', $orgId);
            }
            return $query;
        }

        // Admin sees only their org's users
        return $query->where('organization_id', $user->organization_id);
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::role('user')->with('activeSubscription');
        $this->scopeByOrg($query);

        // Search functionality
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by subscription status
        if ($request->filled('subscription_status')) {
            if ($request->subscription_status === 'active') {
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('status', 'active')
                        ->where('end_date', '>=', now());
                });
            } elseif ($request->subscription_status === 'expired') {
                $query->whereDoesntHave('subscriptions', function ($q) {
                    $q->where('status', 'active')
                        ->where('end_date', '>=', now());
                });
            }
        }

        $users = $query->latest()->paginate(20);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $organizations = [];
        if (auth()->user()->hasRole('superadmin')) {
            $organizations = Organization::where('status', 'active')->get();
        }

        return view('users.create', compact('organizations'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:users'],
            'email' => ['nullable', 'email', 'unique:users'],
        ];

        // Superadmin must select an organization
        if (auth()->user()->hasRole('superadmin')) {
            $rules['organization_id'] = ['required', 'exists:organizations,id'];
        }

        $request->validate($rules);

        // Determine organization_id
        $organizationId = auth()->user()->hasRole('superadmin')
            ? $request->organization_id
            : auth()->user()->organization_id;

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => 'active',
            'organization_id' => $organizationId,
        ]);

        // Assign user role
        $user->assignRole('user');

        // Send welcome SMS
        $welcomeMessage = Setting::get('welcome_sms_message', 'Welcome to GhLinks! Login at starlink.ghprofit.com using your phone number and OTP.');
        SendSmsJob::dispatch($user->phone, $welcomeMessage);

        // Log the action
        AuditLog::log('user_created', null, [
            'created_user_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'organization_id' => $organizationId,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User registered successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Verify org access
        if (!$this->canAccessUser($user)) {
            abort(403);
        }

        $user->load(['subscriptions', 'payments']);

        return view('users.show', compact('user'));
    }

    /**
     * Suspend a user.
     */
    public function suspend(User $user)
    {
        if (!$this->canAccessUser($user)) {
            abort(403);
        }

        if ($user->hasRole(['superadmin', 'admin'])) {
            return back()->with('error', 'Cannot suspend admin users.');
        }

        $user->suspend();

        AuditLog::log('user_suspended', null, [
            'suspended_user_id' => $user->id,
            'name' => $user->name,
        ]);

        return back()->with('success', 'User suspended successfully.');
    }

    /**
     * Activate a user.
     */
    public function activate(User $user)
    {
        if (!$this->canAccessUser($user)) {
            abort(403);
        }

        $user->activate();

        AuditLog::log('user_activated', null, [
            'activated_user_id' => $user->id,
            'name' => $user->name,
        ]);

        return back()->with('success', 'User activated successfully.');
    }

    /**
     * Show unpaid users.
     */
    public function unpaid()
    {
        $query = User::role('user')
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>=', now());
            })
            ->with('payments');

        $this->scopeByOrg($query);

        $users = $query->paginate(20);

        return view('users.unpaid', compact('users'));
    }

    /**
     * Show the form for editing the specified user (superadmin only).
     */
    public function edit(User $user)
    {
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        $organizations = Organization::where('status', 'active')->get();

        return view('users.edit', compact('user', 'organizations'));
    }

    /**
     * Update the specified user (superadmin only).
     */
    public function update(Request $request, User $user)
    {
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:users,phone,' . $user->id],
            'email' => ['nullable', 'email', 'unique:users,email,' . $user->id],
            'organization_id' => ['required', 'exists:organizations,id'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'organization_id' => $request->organization_id,
            'status' => $request->status,
        ]);

        AuditLog::log('user_updated', auth()->user(), [
            'user_id' => $user->id,
            'name' => $user->name,
        ]);

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete the specified user (superadmin only).
     */
    public function destroy(User $user)
    {
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        AuditLog::log('user_deleted', auth()->user(), [
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
        ]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Check if current user can access this user (org scoping).
     */
    private function canAccessUser(User $user): bool
    {
        $currentUser = auth()->user();

        if ($currentUser->hasRole('superadmin')) {
            return true;
        }

        return $user->organization_id === $currentUser->organization_id;
    }
}
