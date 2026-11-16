<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        DB::table('users')->whereNotNull('password_reset_token')->update([
            'password_reset_token' => null,
            'password_token_created_at' => null,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
