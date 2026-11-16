<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graph_sync_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation', 32);
            $table->char('idempotency_key', 64)->unique();
            $table->string('microsoft_event_id');
            $table->string('organizer_microsoft_id');
            $table->string('attendee_email');
            $table->string('attendee_name')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error', 64)->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graph_sync_outbox');
    }
};
