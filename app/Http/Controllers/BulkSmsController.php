<?php

namespace App\Http\Controllers;

use App\Jobs\SendBulkSmsJob;
use App\Models\Organization;
use App\Models\SmsNotification;
use App\Models\User;
use Illuminate\Http\Request;

class BulkSmsController extends Controller
{
    public function index()
    {
        $query = SmsNotification::with(['sender', 'organization'])->latest();

        $user = auth()->user();
        if ($user->hasRole('admin') && $user->organization_id) {
            $query->where('organization_id', $user->organization_id);
        }

        $notifications = $query->paginate(20);

        return view('sms.index', compact('notifications'));
    }

    public function create()
    {
        $organizations = [];
        if (auth()->user()->hasRole('superadmin')) {
            $organizations = Organization::where('status', 'active')->get();
        }

        return view('sms.compose', compact('organizations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:640'],
            'filter_status' => ['nullable', 'in:active,suspended'],
            'filter_subscription' => ['nullable', 'in:unpaid'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
        ]);

        $user = auth()->user();
        $orgId = $user->hasRole('superadmin') ? $request->organization_id : $user->organization_id;

        $query = User::role('user');
        if ($orgId) {
            $query->where('organization_id', $orgId);
        }
        if ($request->filter_status) {
            $query->where('status', $request->filter_status);
        }
        if ($request->filter_subscription === 'unpaid') {
            $query->whereDoesntHave('subscriptions', function ($q) {
                $q->where('status', 'active')->where('end_date', '>=', now());
            });
        }
        $recipientCount = $query->count();

        if ($recipientCount === 0) {
            return back()->with('error', 'No recipients match the selected filters.');
        }

        $notification = SmsNotification::create([
            'sender_id' => $user->id,
            'recipient_count' => $recipientCount,
            'message' => $request->message,
            'filters' => [
                'status' => $request->filter_status,
                'subscription_status' => $request->filter_subscription,
                'organization_id' => $orgId,
            ],
            'status' => 'pending',
            'organization_id' => $orgId,
        ]);

        SendBulkSmsJob::dispatch($notification->id);

        return redirect()->route('sms.index')
            ->with('success', "Bulk SMS queued for {$recipientCount} recipients.");
    }
}
