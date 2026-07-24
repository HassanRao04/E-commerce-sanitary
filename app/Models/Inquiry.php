<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    public const SOURCE_CONTACT_FORM = 'contact_form';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'type',
        'source',
        'ip_address',
        'status',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeContactMessages(Builder $query): Builder
    {
        return $query->where('type', 'contact');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%")
                ->orWhere('source', 'like', "%{$term}%")
                ->orWhere('ip_address', 'like', "%{$term}%");
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (! filled($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeCreatedOn(Builder $query, ?string $date): Builder
    {
        if (! filled($date)) {
            return $query;
        }

        return $query->whereDate('created_at', $date);
    }

    public function updateStatus(InquiryStatus $status): void
    {
        $this->update(['status' => $status]);
    }

    public function markAsPending(): void
    {
        if ($this->status === InquiryStatus::New) {
            $this->updateStatus(InquiryStatus::Pending);
        }
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_CONTACT_FORM => 'Contact form',
            default => filled($this->source) ? str_replace('_', ' ', ucfirst((string) $this->source)) : 'Unknown',
        };
    }

    public function referenceId(): string
    {
        return 'INQ-'.str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }
}
