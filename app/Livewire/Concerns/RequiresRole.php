<?php

namespace App\Livewire\Concerns;

trait RequiresRole
{
    protected function requireRole(string ...$roleNames): void
    {
        $user = auth()->user();

        if ($user === null || $roleNames === []) {
            abort(403);
        }

        foreach ($roleNames as $roleName) {
            if ($user->hasRoleName($roleName)) {
                return;
            }
        }

        abort(403);
    }
}
