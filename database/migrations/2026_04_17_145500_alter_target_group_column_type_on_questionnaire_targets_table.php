<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE questionnaire_targets MODIFY target_group VARCHAR(50) NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE questionnaire_targets ALTER COLUMN target_group TYPE VARCHAR(50)');
        }
    }

    public function down(): void
    {
        // no-op: reverting to old enum set is intentionally skipped
    }
};

