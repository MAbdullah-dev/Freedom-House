<?php

namespace Tests\Feature;

use App\Livewire\Login;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_application_boots(): void
    {
        $this->assertNotNull($this->app);
        $this->assertSame('testing', config('app.env'));
    }

    public function test_public_home_page_renders(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Login');
    }

    public function test_login_livewire_component_renders(): void
    {
        Livewire::test(Login::class)->assertSee('Login');
    }

    public function test_database_connection_is_the_disposable_schema(): void
    {
        $selected = DB::select('select database() as schema_name');

        $this->assertSame('lms_testing', $selected[0]->schema_name);
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('session.driver'));
        $this->assertSame('sync', config('queue.default'));
        $this->assertSame('array', config('mail.default'));
    }

    public function test_role_model_mass_assigns_role_name(): void
    {
        $this->assertTrue(Schema::hasColumn('roles', 'role_name'));
        $this->assertSame(['role_name'], (new Role)->getFillable());
    }
}
