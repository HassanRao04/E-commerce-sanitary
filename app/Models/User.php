<?php

namespace App\Models;

use App\Models\Concerns\NormalizesStrings;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, NormalizesStrings, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->name,
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === 'active',
        );
    }

    protected function initials(): Attribute
    {
        return Attribute::make(
            get: fn (): string => collect(explode(' ', $this->name))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
                ->implode(''),
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

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
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
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
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
}
