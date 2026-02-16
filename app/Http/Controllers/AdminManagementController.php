<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdminManagementController extends Controller
{
    /**
     * Display a listing of admins.
     */
    public function index()
    {
        $admins = User::role(['admin', 'superadmin'])
            ->latest()
            ->paginate(20);

        return view('admins.index', compact('admins'));
    }

    /**
     * Show the form for creating a new admin.
     */
    public function create()
    {
        return view('admins.create');
    }

    /**
     * Store a newly created admin.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:users'],
            'email' => ['nullable', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,superadmin'],
        ]);

        // Only superadmin can create superadmin
        if ($request->role === 'superadmin' && !auth()->user()->hasRole('superadmin')) {
            return back()->with('error', 'Unauthorized action.');
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        // Assign role
        $user->assignRole($request->role);

        // Log the action
        AuditLog::log('admin_created', null, [
            'created_admin_id' => $user->id,
            'name' => $user->name,
            'role' => $request->role,
        ]);

        return redirect()->route('admins.index')
            ->with('success', 'Admin created successfully.');
    }

    /**
     * Remove the specified admin.
     */
    public function destroy(User $admin)
    {
        // Prevent deleting current user
        if ($admin->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        // Only superadmin can delete superadmin
        if ($admin->hasRole('superadmin') && !auth()->user()->hasRole('superadmin')) {
            return back()->with('error', 'Unauthorized action.');
        }

        AuditLog::log('admin_deleted', null, [
            'deleted_admin_id' => $admin->id,
            'name' => $admin->name,
        ]);

        $admin->delete();

        return redirect()->route('admins.index')
            ->with('success', 'Admin deleted successfully.');
    }
}
