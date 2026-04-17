<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('question_id')
                ->constrained('departements')
                ->nullOnDelete();
            $table->index(['department_id', 'created_at']);
        });

        Schema::table('answer_options', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('question_id')
                ->constrained('departements')
                ->nullOnDelete();
            $table->index(['department_id', 'order']);
        });

        DB::statement('
            UPDATE answers a
            INNER JOIN responses r ON r.id = a.response_id
            INNER JOIN users u ON u.id = r.user_id
            SET a.department_id = u.department_id
            WHERE a.department_id IS NULL
        ');

        DB::statement('
            UPDATE answer_options ao
            INNER JOIN questions q ON q.id = ao.question_id
            INNER JOIN questionnaires qq ON qq.id = q.questionnaire_id
            INNER JOIN users u ON u.id = qq.created_by
            SET ao.department_id = u.department_id
            WHERE ao.department_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answer_options', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
