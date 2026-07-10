<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use SoftDeletes;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'browser',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected function actionLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Str::headline(str_replace('.', ' ', $this->action)),
        );
    }

    protected function subjectType(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->model_type ? class_basename($this->model_type) : null,
        );
    }

    protected function action(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::lower(trim($value)),
        );
    }

    #[Scope]
    protected function forUser(Builder $query, int|User $user): void
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        $query->where('user_id', $userId);
    }

    #[Scope]
    protected function forAction(Builder $query, string $action): void
    {
        $query->where('action', Str::lower(trim($action)));
    }

    #[Scope]
    protected function recent(Builder $query, int $days = 30): void
    {
        $query->where('created_at', '>=', now()->subDays($days));
    }

    #[Scope]
    protected function betweenDates(Builder $query, Carbon|string $from, Carbon|string $to): void
    {
        $query->whereBetween('created_at', [
            $from instanceof Carbon ? $from : Carbon::parse($from),
            $to instanceof Carbon ? $to : Carbon::parse($to),
        ]);
    }

    #[Scope]
    protected function forSubject(Builder $query, Model $model): void
    {
        $query
            ->where('model_type', $model::class)
            ->where('model_id', $model->getKey());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
