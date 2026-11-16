<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select('id', 'refresh_token')
            ->whereNotNull('refresh_token')
            ->where('refresh_token', '!=', '')
            ->orderBy('id')
            ->lazy()
            ->each(function ($user) {
                $token = (string) $user->refresh_token;
                if ($this->alreadyEncrypted($token)) {
                    return;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'refresh_token' => Crypt::encryptString($token),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->select('id', 'refresh_token')
            ->whereNotNull('refresh_token')
            ->where('refresh_token', '!=', '')
            ->orderBy('id')
            ->lazy()
            ->each(function ($user) {
                $token = (string) $user->refresh_token;
                if (! $this->alreadyEncrypted($token)) {
                    return;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'refresh_token' => Crypt::decryptString($token),
                ]);
            });
    }

    private function alreadyEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac']);
    }
};
