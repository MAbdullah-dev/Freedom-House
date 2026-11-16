<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Microsoft\MicrosoftIdentityStore;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MicrosoftLinkRaceTest extends TestCase
{
    private array $userIds = [];

    protected function tearDown(): void
    {
        if (config('database.connections.mysql.database') === 'lms_testing') {
            User::query()->whereIn('id', $this->userIds)->forceDelete();
            Role::query()
                ->where('role_name', Role::LEARNER)
                ->whereDoesntHave('users')
                ->delete();
        }

        parent::tearDown();
    }

    public function test_concurrent_linking_allows_only_one_local_account(): void
    {
        $role = Role::query()->firstOrCreate(['role_name' => Role::LEARNER]);
        $tid = 'abababab-abab-4aba-8bab-abababababab';
        $oid = 'cdcdcdcd-cdcd-4cdc-8dcd-cdcdcdcdcdcd';
        $users = [];
        foreach (['a', 'b'] as $suffix) {
            $user = User::query()->create([
                'email' => 'race-link-'.$suffix.'-'.bin2hex(random_bytes(3)).'@example.test',
                'password' => Hash::make('secret-secret'),
                'role_id' => $role->id,
                'email_verified_at' => now(),
                'microsoft_account' => false,
                'timezone' => 'UTC',
            ]);
            $this->userIds[] = $user->id;
            $users[] = $user;
        }

        $output = '';
        $processes = [];
        foreach ($users as $user) {
            $json = storage_path('framework/microsoft-link-'.bin2hex(random_bytes(4)).'.json');
            file_put_contents($json, json_encode([
                'user_id' => $user->id,
                'tid' => $tid,
                'oid' => $oid,
            ]));
            $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('tests/Support/microsoft_link_race.php')).' '.escapeshellarg($json);
            $pipes = [];
            $handle = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
            $processes[] = ['handle' => $handle, 'pipes' => $pipes, 'json' => $json];
        }

        foreach ($processes as $process) {
            $stdout = stream_get_contents($process['pipes'][1]);
            $stderr = stream_get_contents($process['pipes'][2]);
            fclose($process['pipes'][1]);
            fclose($process['pipes'][2]);
            $exit = proc_close($process['handle']);
            @unlink($process['json']);
            $output .= $stdout."\n".$stderr."\n";
            $this->assertSame(0, $exit, $stderr."\n".$stdout);
            $this->assertStringNotContainsString('SQLSTATE', $stderr);
        }

        $key = MicrosoftIdentityStore::identityKey($tid, $oid);
        $this->assertSame(1, User::query()->where('microsoft_identity_key', $key)->count(), $output);
        $this->assertStringContainsString(MicrosoftIdentityStore::LINKED, $output);
        $this->assertStringContainsString(MicrosoftIdentityStore::DUPLICATE, $output);
    }
}
