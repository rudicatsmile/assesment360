<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnswerOption extends Model
{
    /** @use HasFactory<\Database\Factories\AnswerOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'department_id',
        'option_text',
        'score',
        'order',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function departmentRef(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'department_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
