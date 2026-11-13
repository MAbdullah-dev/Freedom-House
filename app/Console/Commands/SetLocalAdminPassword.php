<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SetLocalAdminPassword extends Command
{
    protected $signature = 'admin:password {email=admin@example.com}';

    protected $description = 'Set a local administrator password from a hidden prompt';

    public function handle(): int
    {
        if (! $this->targetsApprovedLocalDatabase()) {
            $this->error('Refusing to change a password outside the local development database.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('No local account exists for that email.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('New password');
        $confirmation = (string) $this->secret('Confirm password');

        if ($password === '' || $password !== $confirmation) {
            $this->error('The password confirmation did not match.');

            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('Use at least 12 characters.');

            return self::FAILURE;
        }

        DB::disableQueryLog();
        $user->forceFill([
            'password' => Hash::make($password),
        ])->save();

        $this->info('Local account password updated.');

        return self::SUCCESS;
    }

    private function targetsApprovedLocalDatabase(): bool
    {
        $connection = (string) config('database.default');
        $host = (string) config("database.connections.{$connection}.host");
        $port = (string) config("database.connections.{$connection}.port");
        $database = (string) config("database.connections.{$connection}.database");
        $url = (string) config("database.connections.{$connection}.url");

        if ($url !== '' || ! in_array($host, ['127.0.0.1', 'localhost'], true) || $port !== '3306') {
            return false;
        }

        if (app()->environment('local') && $database === 'lms') {
            return true;
        }

        return app()->environment('testing') && $database === 'lms_testing';
    }
}
