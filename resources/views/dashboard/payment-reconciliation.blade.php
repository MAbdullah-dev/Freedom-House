<x-layouts.dashboard>
    <div class="dashboard py-5 px-4 w-100">
        <h4 class="mb-3">Payment reconciliation</h4>
        <p class="mb-4">Unresolved payments: {{ $unresolvedCount }}</p>

        <form method="GET" action="{{ route('dashboard.payments.reconciliation') }}" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="resolution_required" class="form-label">Resolution required</label>
                <select id="resolution_required" name="resolution_required" class="form-select">
                    <option value="" @selected($resolutionRequired === '')>All</option>
                    <option value="yes" @selected($resolutionRequired === 'yes')>Yes</option>
                    <option value="no" @selected($resolutionRequired === 'no')>No</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="resolution_reason" class="form-label">Resolution reason</label>
                <select id="resolution_reason" name="resolution_reason" class="form-select">
                    <option value="" @selected($resolutionReason === '')>All</option>
                    @foreach ($reasons as $reason)
                        <option value="{{ $reason }}" @selected($resolutionReason === $reason)>{{ $reason }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="button-primary">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Refunded</th>
                        <th>Booking</th>
                        <th>Stripe session</th>
                        <th>Payment intent</th>
                        <th>Resolution</th>
                        <th>Reason</th>
                        <th>Created</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->status }}</td>
                            <td>
                                @if ($payment->amount_cents !== null)
                                    {{ number_format($payment->amount_cents / 100, 2, '.', '') }}
                                @else
                                    {{ $payment->amount }}
                                @endif
                            </td>
                            <td>{{ $payment->currency }}</td>
                            <td>{{ number_format(((int) $payment->refunded_cents) / 100, 2, '.', '') }}</td>
                            <td>{{ $payment->booking_id ?? '—' }}</td>
                            <td>{{ $payment->stripe_session_id }}</td>
                            <td>{{ $payment->stripe_payment_intent_id }}</td>
                            <td>{{ $payment->resolution_required ? 'required' : 'clear' }}</td>
                            <td>{{ $payment->resolution_reason }}</td>
                            <td>{{ $payment->created_at }}</td>
                            <td>{{ $payment->updated_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">No payments match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $payments->links() }}
    </div>
</x-layouts.dashboard>
