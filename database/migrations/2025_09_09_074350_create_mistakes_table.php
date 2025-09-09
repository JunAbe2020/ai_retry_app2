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
        Schema::create('mistakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('happened_at');
            $table->text('situation');
            $table->text('cause');
            $table->text('my_solution');
            $table->longText('ai_notes')->nullable();
            $table->text('supplement')->nullable();
            $table->longText('re_ai_notes')->nullable();
            $table->dateTime('reminder_date')->nullable();
            $table->boolean('is_reminded')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mistakes');
    }
};
