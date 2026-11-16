<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRoleName(Role::ADMIN), 403);

        $statuses = [
            Payment::STATUS_PAID,
            Payment::STATUS_UNFULFILLABLE,
            Payment::STATUS_PARTIALLY_REFUNDED,
            Payment::STATUS_REFUNDED,
        ];

        $knownReasons = [
            'duplicate_enrollment',
            'capacity_exhausted',
            'amount_mismatch',
            'currency_mismatch',
            'metadata_mismatch',
            'missing_item',
            'seat_not_released',
            'refunded_before_completion',
        ];

        $storedReasons = Payment::query()
            ->whereIn('status', $statuses)
            ->whereNotNull('resolution_reason')
            ->distinct()
            ->pluck('resolution_reason');

        $reasons = collect($knownReasons)
            ->merge($storedReasons)
            ->filter(fn ($reason) => is_string($reason) && preg_match('/^[a-z0-9_]{1,64}$/', $reason) === 1)
            ->unique()
            ->sort()
            ->values();

        $payments = Payment::query()
            ->with('booking')
            ->whereIn('status', $statuses);

        $required = $request->query('resolution_required');
        if ($required === 'yes') {
            $payments->where('resolution_required', true);
        } elseif ($required === 'no') {
            $payments->where('resolution_required', false);
        }

        $reason = $request->query('resolution_reason');
        if (is_string($reason) && $reasons->contains($reason)) {
            $payments->where('resolution_reason', $reason);
        }

        return view('dashboard.payment-reconciliation', [
            'payments' => $payments->orderByDesc('id')->paginate(20)->withQueryString(),
            'reasons' => $reasons,
            'unresolvedCount' => Payment::query()
                ->whereIn('status', $statuses)
                ->where('resolution_required', true)
                ->count(),
            'resolutionRequired' => is_string($required) ? $required : '',
            'resolutionReason' => is_string($reason) ? $reason : '',
        ]);
    }
}
