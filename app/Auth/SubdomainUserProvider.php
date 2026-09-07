<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class SubdomainUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        $query = $this->newModelQuery();

        // Add subdomain filtering
        $currentSubdomain = app('current_subdomain');
        if ($currentSubdomain) {
            $query->where('subdomain_id', $currentSubdomain->id);
        }

        foreach ($credentials as $key => $value) {
            if (! str_contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Verify user belongs to current subdomain
        $currentSubdomain = app('current_subdomain');
        if ($currentSubdomain && $user->subdomain_id !== $currentSubdomain->id) {
            return false;
        }

        return parent::validateCredentials($user, $credentials);
    }
}
