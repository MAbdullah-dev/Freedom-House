<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Create a local administrator only when the operator supplies credentials
     * for this process. Existing accounts are left unchanged.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $email = $this->configured('LOCAL_ADMIN_EMAIL');
        $password = $this->configured('LOCAL_ADMIN_PASSWORD');

        if ($email === null || $password === null || User::query()->where('email', $email)->exists()) {
            return;
        }

        $admin = Role::findByName(Role::ADMIN);

        if ($admin === null) {
            return;
        }

        User::query()->create([
            'email' => $email,
            'first_name' => 'Local',
            'last_name' => 'Administrator',
            'password' => Hash::make($password),
            'role_id' => $admin->id,
            'email_verified_at' => now(),
            'microsoft_account' => false,
            'timezone' => 'UTC',
        ]);
    }

    private function configured(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
