<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add unique composite indexes to prevent duplicate votes.
     */
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->unique(['poll_id', 'user_id'], 'votes_poll_user_unique');
            $table->unique(['poll_id', 'ip_address'], 'votes_poll_ip_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('votes_poll_user_unique');
            $table->dropUnique('votes_poll_ip_unique');
        });
    }
};
