<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::role('user')->with('activeSubscription');

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
        return view('users.create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:users'],
            'email' => ['nullable', 'email', 'unique:users'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => 'active',
        ]);

        // Assign user role
        $user->assignRole('user');

        // Log the action
        AuditLog::log('user_created', null, [
            'created_user_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User registered successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['subscriptions', 'payments']);

        return view('users.show', compact('user'));
    }

    /**
     * Suspend a user.
     */
    public function suspend(User $user)
    {
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
        $users = User::role('user')
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>=', now());
            })
            ->with('payments')
            ->paginate(20);

        return view('users.unpaid', compact('users'));
    }
}
