<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'login_code',
        'active',
        'can_access_billing',
        'can_manage_users',
        'default_area',
        'default_book_business_id',
        'theme_mode',
    ];

    protected $hidden = [
        'password',
        'login_code',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'login_code' => 'hashed',
            'active' => 'boolean',
            'can_access_billing' => 'boolean',
            'can_manage_users' => 'boolean',
        ];
    }

    public function bookBusinesses(): BelongsToMany
    {
        return $this->belongsToMany(BookBusiness::class, 'book_business_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function defaultBookBusiness(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'default_book_business_id');
    }

    public function canAccessBilling(): bool
    {
        return $this->active && $this->can_access_billing;
    }

    public function canAccessBooks(): bool
    {
        return $this->active && $this->bookBusinesses()->where('book_businesses.active', true)->exists();
    }

    public function canAccessBookBusiness(BookBusiness|int $business): bool
    {
        $businessId = $business instanceof BookBusiness ? $business->getKey() : $business;

        return $this->active
            && $this->bookBusinesses()
                ->where('book_businesses.id', $businessId)
                ->where('book_businesses.active', true)
                ->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->active && ($this->canAccessBilling() || $this->canAccessBooks());
    }
}
