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
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade'); // Assignment being submitted
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // User submitting the assignment
            $table->string('file_path')->nullable(); // Path to the submitted file
            $table->enum('grade', ['A', 'B', 'C', 'D', 'E', 'F'])->nullable(); // Grade for the submission
            $table->boolean('is_graded')->default(false); // Whether the submission has been graded
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
