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
        Schema::table('rooms', function (Blueprint $table) {
            $table->integer('discussion_time')->default(60)->after('category'); // Discussion time in seconds
            $table->timestamp('phase_started_at')->nullable()->after('discussion_time'); // When current phase started
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['discussion_time', 'phase_started_at']);
        });
    }
};
