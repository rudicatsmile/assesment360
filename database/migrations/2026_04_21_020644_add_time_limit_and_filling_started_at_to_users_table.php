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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('time_limit_minutes')->nullable()->after('is_active')->comment('Total time allocated for filling all questionnaires (in minutes). Null = no limit.');
            $table->timestamp('filling_started_at')->nullable()->after('time_limit_minutes')->comment('When the user started the current fill session.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['time_limit_minutes', 'filling_started_at']);
        });
    }
};
