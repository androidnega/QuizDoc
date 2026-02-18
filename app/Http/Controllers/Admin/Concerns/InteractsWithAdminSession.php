<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\User;

trait InteractsWithAdminSession
{
    protected function adminUser(): ?User
    {
        $user = auth()->user();
        if ($user) {
            return $user;
        }

        $adminUserId = session('admin_user_id');
        return $adminUserId ? User::find($adminUserId) : null;
    }

    /** Route prefix for redirects: unified dashboard. */
    protected function staffRoutePrefix(): string
    {
        return 'dashboard';
    }
}
