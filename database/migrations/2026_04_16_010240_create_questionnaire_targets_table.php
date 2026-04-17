<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained()->onDelete('cascade');
            $table->string('target_group', 50);
            $table->timestamps();

            $table->unique(['questionnaire_id', 'target_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_targets');
    }
};
