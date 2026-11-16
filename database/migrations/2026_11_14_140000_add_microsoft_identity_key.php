<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('users')
            ->select('microsoft_id')
            ->whereNotNull('microsoft_id')
            ->where('microsoft_id', '!=', '')
            ->groupBy('microsoft_id')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Refusing to add a Microsoft identity key because duplicate microsoft_id values already exist.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('microsoft_tenant_id', 36)->nullable()->after('microsoft_id');
            $table->string('microsoft_identity_key', 320)->nullable()->after('microsoft_tenant_id');
        });

        DB::table('users')
            ->select('id', 'microsoft_id')
            ->whereNotNull('microsoft_id')
            ->where('microsoft_id', '!=', '')
            ->whereNull('microsoft_identity_key')
            ->orderBy('id')
            ->lazy()
            ->each(function ($user) {
                DB::table('users')->where('id', $user->id)->update([
                    'microsoft_identity_key' => 'legacy:'.strtolower((string) $user->microsoft_id),
                ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('microsoft_identity_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['microsoft_identity_key']);
            $table->dropColumn(['microsoft_tenant_id', 'microsoft_identity_key']);
        });
    }
};
