<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        $this->clearLocalAdminConfiguration();

        parent::tearDown();
    }

    public function test_category_and_tag_seeders_can_run_twice_without_duplicates(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(TagSeeder::class);
        $this->seed(TagSeeder::class);

        $this->assertSame(
            ['ETHICS', 'MENTAL HEALTH', 'SUBSTANCE USE DISORDER'],
            Category::query()->orderBy('category_name')->pluck('category_name')->all()
        );
        $this->assertSame(3, Category::query()->count());
        $this->assertSame(7, Tag::query()->count());
        $this->assertSame(7, Tag::query()->distinct()->count('name'));
    }

    public function test_user_seeder_does_not_embed_or_overwrite_a_password(): void
    {
        $source = file_get_contents(database_path('seeders/UserSeeder.php'));

        $this->assertDoesNotMatchRegularExpression('/Hash::make\(\s*[\'"]/', $source);
        $this->assertStringNotContainsString("'role_id' => 1", $source);

        $this->seed(UserSeeder::class);
        $this->assertSame(0, User::query()->count());

        $existingPassword = bin2hex(random_bytes(16));
        $user = User::query()->create([
            'email' => 'kept-admin@example.test',
            'password' => Hash::make($existingPassword),
            'role_id' => 3,
        ]);
        $originalHash = $user->password;

        $this->configureLocalAdmin($user->email, bin2hex(random_bytes(16)));
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $user->refresh();

        $this->assertSame(1, User::query()->count());
        $this->assertSame($originalHash, $user->password);
        $this->assertSame(3, $user->role_id);
        $this->assertTrue(Hash::check($existingPassword, $user->password));
    }

    public function test_user_seeder_assigns_the_admin_role_by_name(): void
    {
        Role::query()->create(['role_name' => Role::LEARNER]);
        $admin = Role::query()->create(['role_name' => Role::ADMIN]);

        $email = 'named-admin@example.test';
        $password = bin2hex(random_bytes(16));
        $this->configureLocalAdmin($email, $password);

        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $user = User::query()->where('email', $email)->first();

        $this->assertNotNull($user);
        $this->assertSame(1, User::query()->count());
        $this->assertSame($admin->id, $user->role_id);
        $this->assertNotSame(1, $user->role_id);
        $this->assertTrue($user->hasRoleName(Role::ADMIN));
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_user_seeder_skips_non_local_environments(): void
    {
        $this->app['env'] = 'production';
        Role::query()->create(['role_name' => Role::ADMIN]);
        $this->configureLocalAdmin('production-admin@example.test', bin2hex(random_bytes(16)));

        (new UserSeeder)->run();

        $this->assertSame(0, User::query()->count());
    }

    public function test_local_password_command_updates_only_the_selected_account(): void
    {
        $user = User::query()->create([
            'email' => 'command-admin@example.test',
            'password' => Hash::make(bin2hex(random_bytes(16))),
        ]);
        $replacement = bin2hex(random_bytes(16));

        $this->artisan('admin:password', ['email' => $user->email])
            ->expectsQuestion('New password', $replacement)
            ->expectsQuestion('Confirm password', $replacement)
            ->assertExitCode(0);

        $user->refresh();

        $this->assertTrue(Hash::check($replacement, $user->password));
    }

    public function test_local_password_command_refuses_another_database(): void
    {
        config(['database.connections.mysql.database' => 'lms']);

        $this->artisan('admin:password')
            ->assertExitCode(1);
    }

    private function configureLocalAdmin(string $email, string $password): void
    {
        foreach ([
            'LOCAL_ADMIN_EMAIL' => $email,
            'LOCAL_ADMIN_PASSWORD' => $password,
        ] as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function clearLocalAdminConfiguration(): void
    {
        foreach (['LOCAL_ADMIN_EMAIL', 'LOCAL_ADMIN_PASSWORD'] as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
}
