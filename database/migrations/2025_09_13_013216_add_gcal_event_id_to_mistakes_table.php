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
        Schema::table('mistakes', function (Blueprint $table) {
            $table->string('gcal_event_id')->nullable()->after('is_reminded')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropColumn('gcal_event_id');
        });
    }
};
