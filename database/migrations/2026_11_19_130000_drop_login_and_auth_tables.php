<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop all tables related to login, authentication, tokens, and sessions.
     */
    public function up(): void
    {
        Schema::dropIfExists('change_request_personal_access_tokens');
        Schema::dropIfExists('change_request_password_reset_tokens');
        Schema::dropIfExists('change_request_sessions');
        Schema::dropIfExists('change_request_users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation as the system strictly operates without authentication
    }
};
