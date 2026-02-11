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
        Schema::table('votes', function (Blueprint $table) {
            $table->string('voter_token')->nullable()->after('ip_address');
            $table->unique(['poll_id', 'voter_token'], 'votes_poll_voter_token_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('votes_poll_voter_token_unique');
            $table->dropColumn('voter_token');
        });
    }
};
