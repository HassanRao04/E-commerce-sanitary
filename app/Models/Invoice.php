<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\FormatsMoney;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use FormatsMoney;
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax_total',
        'discount_total',
        'offer_discount_total',
        'shipping_total',
        'shipping_discount_total',
        'total',
        'issued_at',
        'due_at',
        'paid_at',
        'billing_name',
        'billing_email',
        'billing_address',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'offer_discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'shipping_discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected function formattedTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute($this->total),
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === InvoiceStatus::Paid || filled($this->paid_at),
        );
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => ! $this->is_paid
                && filled($this->due_at)
                && $this->due_at->isPast()
                && $this->status !== InvoiceStatus::Void,
        );
    }

    protected function pdfUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->pdf_path) ? \Illuminate\Support\Facades\Storage::url($this->pdf_path) : null,
        );
    }

    protected function invoiceNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value ? Str::upper($value) : null,
        );
    }

    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Draft);
    }

    #[Scope]
    protected function issued(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Issued);
    }

    #[Scope]
    protected function paid(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Paid);
    }

    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Overdue)
            ->orWhere(function (Builder $builder): void {
                $builder->whereNotIn('status', [InvoiceStatus::Paid, InvoiceStatus::Void])
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now());
            });
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('invoice_number', 'like', "%{$term}%")
                ->orWhere('billing_name', 'like', "%{$term}%")
                ->orWhere('billing_email', 'like', "%{$term}%");
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
