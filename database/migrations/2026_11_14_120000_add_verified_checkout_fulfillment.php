<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('bookings')
            ->select('user_id', 'course_id', 'class_id', 'event_id')
            ->whereIn('payment_status', ['paid', 'free'])
            ->whereNotNull('user_id')
            ->groupBy('user_id', 'course_id', 'class_id', 'event_id')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Refusing to add an active enrollment key because duplicate paid or free bookings already exist.'
            );
        }

        Schema::create('checkout_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 16);
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3);
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('state', 32)->default('open');
            $table->timestamps();
            $table->index(['item_type', 'item_id']);
        });

        Schema::create('stripe_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('stripe_refund_notices', function (Blueprint $table) {
            $table->id();
            $table->string('payment_intent_id')->unique();
            $table->unsignedInteger('amount_refunded')->default(0);
            $table->char('currency', 3)->nullable();
            $table->boolean('fully_refunded')->default(false);
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('active_enrollment_key')->nullable()->unique();
        });

        DB::table('bookings')
            ->whereIn('payment_status', ['paid', 'free'])
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->lazy()
            ->each(function ($booking) {
                $key = $this->enrollmentKey(
                    $booking->user_id,
                    $booking->course_id,
                    $booking->class_id,
                    $booking->event_id
                );
                if ($key !== null) {
                    DB::table('bookings')->where('id', $booking->id)->update([
                        'active_enrollment_key' => $key,
                    ]);
                }
            });

        DB::statement('ALTER TABLE payments MODIFY booking_id BIGINT UNSIGNED NULL');

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('checkout_snapshot_id')->nullable()->constrained('checkout_snapshots')->nullOnDelete();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->unsignedInteger('amount_cents')->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('status', 32)->nullable();
            $table->unsignedInteger('refunded_cents')->default(0);
            $table->boolean('resolution_required')->default(false);
            $table->string('resolution_reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('checkout_snapshot_id');
            $table->dropUnique(['stripe_session_id']);
            $table->dropIndex(['stripe_payment_intent_id']);
            $table->dropColumn([
                'stripe_session_id',
                'stripe_payment_intent_id',
                'amount_cents',
                'currency',
                'status',
                'refunded_cents',
                'resolution_required',
                'resolution_reason',
            ]);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['active_enrollment_key']);
            $table->dropColumn('active_enrollment_key');
        });

        Schema::dropIfExists('stripe_refund_notices');
        Schema::dropIfExists('stripe_events');
        Schema::dropIfExists('checkout_snapshots');
    }

    private function enrollmentKey(mixed $userId, mixed $courseId, mixed $classId, mixed $eventId): ?string
    {
        if (! is_numeric($userId)) {
            return null;
        }

        $parts = [];
        if (is_numeric($courseId)) {
            $parts[] = 'course:'.(int) $courseId;
        }
        if (is_numeric($classId)) {
            $parts[] = 'class:'.(int) $classId;
        }
        if (is_numeric($eventId)) {
            $parts[] = 'event:'.(int) $eventId;
        }

        if (count($parts) !== 1) {
            return null;
        }

        return 'user:'.(int) $userId.':'.$parts[0];
    }
};
