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
        Schema::create('change_request_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('change_no', 50)->unique();
            $table->timestamp('create_date')->useCurrent();
            $table->string('subject', 255);
            $table->string('operate_date', 100);
            $table->string('operate_time', 50);
            $table->string('user_operate', 200);
            $table->text('remark')->nullable();
            $table->string('alert_badge', 100)->nullable();
            $table->text('alert_message')->nullable();
            $table->timestamps();

            $table->index('create_date');
            $table->index('user_operate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_request_announcements');
    }
};
