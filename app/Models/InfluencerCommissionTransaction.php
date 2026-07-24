<?php

namespace App\Models;

use App\Enums\CommissionLedgerStatus;
use App\Enums\CommissionLedgerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerCommissionTransaction extends Model
{
    protected $fillable = [
        'influencer_id',
        'type',
        'order_id',
        'reference_order_id',
        'amount',
        'admin_notes',
        'transaction_id',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => CommissionLedgerType::class,
            'status' => CommissionLedgerStatus::class,
            'amount' => 'decimal:2',
        ];
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function referenceOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'reference_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Order used for display (credit row or debit payout reference).
     */
    public function relatedOrder(): ?Order
    {
        return $this->order ?? $this->referenceOrder;
    }

    /**
     * Credit amount always comes from the related order (not stored on the row).
     */
    public function creditAmount(): float
    {
        if ($this->type !== CommissionLedgerType::Credit) {
            return 0.0;
        }

        return round((float) ($this->order?->influencer_commission_amount ?? 0), 2);
    }

    public function debitAmount(): float
    {
        if ($this->type !== CommissionLedgerType::Debit) {
            return 0.0;
        }

        if ($this->status === CommissionLedgerStatus::Cancelled) {
            return 0.0;
        }

        return round((float) ($this->amount ?? 0), 2);
    }

    public function displayStatus(): string
    {
        if ($this->type === CommissionLedgerType::Credit) {
            return $this->order?->influencer_commission_paid_at ? 'Paid' : 'Pending';
        }

        return $this->status?->label() ?? '—';
    }
}
