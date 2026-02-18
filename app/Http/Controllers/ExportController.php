<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function payments(Request $request): StreamedResponse
    {
        $user = auth()->user();

        return response()->streamDownload(function () use ($request, $user) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'User', 'Phone', 'Reference', 'Base Amount', 'Service Charge', 'Total', 'Status', 'Provider']);

            $query = Payment::with('user');

            if ($user->hasRole('admin') && $user->organization_id) {
                $query->whereHas('user', fn($q) => $q->where('organization_id', $user->organization_id));
            } elseif ($user->hasRole('superadmin') && session('admin_org_filter')) {
                $query->whereHas('user', fn($q) => $q->where('organization_id', session('admin_org_filter')));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $query->latest()->chunk(500, function ($payments) use ($handle) {
                foreach ($payments as $payment) {
                    fputcsv($handle, [
                        $payment->created_at->format('Y-m-d H:i'),
                        $payment->user?->name ?? 'N/A',
                        $payment->user?->phone ?? 'N/A',
                        $payment->reference,
                        $payment->base_amount ?? $payment->amount,
                        $payment->service_charge ?? 0,
                        $payment->amount,
                        $payment->status,
                        $payment->payment_provider,
                    ]);
                }
            });

            fclose($handle);
        }, 'payments-' . now()->format('Y-m-d') . '.csv');
    }

    public function users(Request $request): StreamedResponse
    {
        $user = auth()->user();

        return response()->streamDownload(function () use ($request, $user) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Phone', 'Email', 'Status', 'Organization', 'Total Paid', 'Registered']);

            $query = User::role('user')->with('organization');

            if ($user->hasRole('admin') && $user->organization_id) {
                $query->where('organization_id', $user->organization_id);
            } elseif ($user->hasRole('superadmin') && session('admin_org_filter')) {
                $query->where('organization_id', session('admin_org_filter'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $query->latest()->chunk(500, function ($users) use ($handle) {
                foreach ($users as $u) {
                    fputcsv($handle, [
                        $u->name,
                        $u->phone,
                        $u->email ?? 'N/A',
                        $u->status,
                        $u->organization?->name ?? 'N/A',
                        $u->getTotalPaid(),
                        $u->created_at->format('Y-m-d'),
                    ]);
                }
            });

            fclose($handle);
        }, 'users-' . now()->format('Y-m-d') . '.csv');
    }
}
