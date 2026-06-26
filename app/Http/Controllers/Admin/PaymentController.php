<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with('order')
            ->when($request->filled('q'), fn ($q) => $q->search($request->input('q')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('gateway'), fn ($q) => $q->where('gateway', $request->input('gateway')))
            ->recent()
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load('order', 'refunds');

        return view('admin.payments.show', compact('payment'));
    }
}
