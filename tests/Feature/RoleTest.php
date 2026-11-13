<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seeder_creates_named_roles_without_duplicates(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $roles = Role::query()->orderBy('role_name')->get();

        $this->assertSame(
            [Role::ADMIN, Role::LEARNER, Role::TUTOR],
            $roles->pluck('role_name')->all()
        );
        $this->assertCount(3, $roles);
        $this->assertCount(3, $roles->pluck('id')->unique());
        $this->assertNull(Role::findByName('1'));
    }

    public function test_user_role_relationship_resolves_by_name(): void
    {
        $this->seed(RoleSeeder::class);

        $learner = Role::findByName(Role::LEARNER);

        $user = User::create([
            'email' => 'learner-role@example.test',
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => $learner->id,
        ]);

        $this->assertTrue($user->role->is($learner));
        $this->assertSame(Role::LEARNER, $user->role->role_name);
        $this->assertTrue($user->hasRoleName(Role::LEARNER));
        $this->assertTrue($learner->users->contains(fn (User $member) => $member->is($user)));
    }

    public function test_missing_role_name_does_not_resolve_to_a_record(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertNull(Role::findByName('Owner'));

        $user = User::create([
            'email' => 'missing-role@example.test',
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'role_id' => 0,
        ]);

        $this->assertNull($user->role);
        $this->assertFalse($user->hasRoleName(Role::ADMIN));
        $this->assertFalse($user->hasRoleName(Role::TUTOR));
        $this->assertFalse($user->hasRoleName(Role::LEARNER));
    }
}
