<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->cascadeOnUpdate();
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade')->cascadeOnUpdate();
            $table->string('title');
            $table->text('description');
            $table->integer('capacity')->nullable();
            $table->boolean('visibility');
            $table->enum('class_type', ['onsite', 'virtual', 'recorded']);
            $table->string('teams_link')->nullable();
            $table->string('onsite_address')->nullable();
            $table->string('recorded_video_url')->nullable();
            $table->enum('is_paid', ['free', 'paid'])->default('free');
            $table->decimal('price', 8, 2)->nullable();
            $table->time('class_time');
            $table->date('class_date');
            $table->dateTime('booking_start_date')->nullable();
            $table->dateTime('booking_end_date')->nullable();
            $table->integer('max_students')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
