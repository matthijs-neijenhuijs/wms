<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants, HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
                'password' => 'hashed',
                'app_authentication_secret' => 'encrypted',
                'app_authentication_recovery_codes' => 'encrypted:array',
                'has_email_authentication' => 'boolean',
        ];
    }

        public function getAppAuthenticationSecret(): ?string
        {
            return $this->app_authentication_secret;
        }

        public function saveAppAuthenticationSecret(?string $secret): void
        {
            $this->app_authentication_secret = $secret;
            $this->save();
        }

        public function getAppAuthenticationHolderName(): string
        {
            return $this->email;
        }

        public function getAppAuthenticationRecoveryCodes(): ?array
        {
            return $this->app_authentication_recovery_codes;
        }

        public function saveAppAuthenticationRecoveryCodes(?array $codes): void
        {
            $this->app_authentication_recovery_codes = $codes;
            $this->save();
        }

        public function hasEmailAuthentication(): bool
        {
            return $this->has_email_authentication;
        }

        public function toggleEmailAuthentication(bool $condition): void
        {
            $this->has_email_authentication = $condition;
            $this->save();
        }
        public function canAccessPanel(\Filament\Panel $panel): bool
        {
            // Allow all authenticated users to access the panel. Adjust logic as needed.
            return true;
        }

        public function warehouses(){
            return $this->belongsToMany(Warehouse::class, 'user_warehouses');
        }

        public function getTenants(Panel $panel): Collection
        {
            return $this->warehouses;
        }

        public function canAccessTenant(Model $tenant): bool
        {
            return $this->warehouses()->whereKey($tenant)->exists();
        }


}
