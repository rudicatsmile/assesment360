<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('option_text');
            $table->integer('score')->nullable();
            $table->integer('order');
            $table->timestamps();

            $table->unique(['question_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_options');
    }
};
