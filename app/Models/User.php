<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Concerns\NormalizesStrings;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property UserStatus|null $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ActivityLog> $activityLogs
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, NormalizesStrings, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'profile_photo',
        'status',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'last_login_ip',
        'suspended_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
            'deleted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty(['first_name', 'last_name'])) {
                $composed = trim(collect([$user->first_name, $user->last_name])->filter()->implode(' '));

                if ($composed !== '') {
                    $user->attributes['name'] = $composed;
                }
            }

            if ($user->isDirty('status')) {
                $status = $user->status instanceof UserStatus
                    ? $user->status
                    : UserStatus::tryFrom((string) $user->status);

                $user->suspended_at = $status === UserStatus::Suspended
                    ? ($user->suspended_at ?? now())
                    : null;
            }
        });
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->full_name,
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $composed = trim(collect([$this->first_name, $this->last_name])->filter()->implode(' '));

                return $composed !== '' ? $composed : (string) ($this->attributes['name'] ?? '');
            },
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === UserStatus::Active,
        );
    }

    protected function isSuspended(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === UserStatus::Suspended,
        );
    }

    protected function isInactive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === UserStatus::Inactive,
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->status?->label() ?? 'Unknown',
        );
    }

    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->profile_photo)
                ? Storage::url($this->profile_photo)
                : null,
        );
    }

    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $parts = collect([$this->first_name, $this->last_name])
                    ->filter()
                    ->take(2);

                if ($parts->isNotEmpty()) {
                    return $parts
                        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
                        ->implode('');
                }

                return collect(explode(' ', (string) ($this->attributes['name'] ?? '')))
                    ->filter()
                    ->take(2)
                    ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
                    ->implode('');
            },
        );
    }

    protected function isVerified(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => filled($this->email_verified_at),
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeLower($value),
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    protected function firstName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', UserStatus::Active);
    }

    #[Scope]
    protected function inactive(Builder $query): void
    {
        $query->where('status', UserStatus::Inactive);
    }

    #[Scope]
    protected function suspended(Builder $query): void
    {
        $query->where('status', UserStatus::Suspended);
    }

    #[Scope]
    protected function staff(Builder $query): void
    {
        $query->role([
            'super-admin',
            'admin',
            'manager',
            'inventory-staff',
            'sales-staff',
            'content-manager',
        ]);
    }

    #[Scope]
    protected function customers(Builder $query): void
    {
        $query->role('customer');
    }

    #[Scope]
    protected function withStatus(Builder $query, UserStatus|string $status): void
    {
        $value = $status instanceof UserStatus ? $status : UserStatus::tryFrom($status);

        if ($value !== null) {
            $query->where('status', $value);
        }
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('name', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function inAppNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function compareList(): HasOne
    {
        return $this->hasOne(CompareList::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    /** @deprecated Use customer() */
    public function customerProfile(): HasOne
    {
        return $this->customer();
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole([
            'super-admin',
            'admin',
            'manager',
            'inventory-staff',
            'sales-staff',
            'content-manager',
        ]);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function canAccessAdmin(): bool
    {
        return $this->isStaff() && ($this->status?->canAccessAdmin() ?? false);
    }
}
