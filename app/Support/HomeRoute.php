<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

/**
 * Where a signed-in user belongs: the admin dashboard for admins and
 * managers, the register for cashiers (who aren't allowed on the dashboard).
 * Used after login, for "/", and when a signed-in user opens a guest page.
 */
class HomeRoute
{
    public static function for(?User $user): string
    {
        if ($user === null) {
            return route('login');
        }

        return $user->hasAnyRole([Role::Admin->value, Role::Manager->value])
            ? route('admin.dashboard')
            : route('pos.register');
    }
}
