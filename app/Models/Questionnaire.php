<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Questionnaire extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionnaireFactory> */
    use HasFactory, SoftDeletes;

    public const array TARGET_GROUPS = ['guru', 'tata_usaha', 'orang_tua'];

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(QuestionnaireTarget::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * @param array<int, string> $targetGroups
     */
    public function syncTargetGroups(array $targetGroups): void
    {
        $normalized = array_values(array_unique(
            array_filter(
                $targetGroups,
                fn(mixed $value): bool => is_string($value) && in_array($value, self::TARGET_GROUPS, true)
            )
        ));

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'target_groups' => 'Minimal 1 target group wajib dipilih.',
            ]);
        }

        DB::transaction(function () use ($normalized): void {
            $this->targets()
                ->whereNotIn('target_group', $normalized)
                ->delete();

            foreach ($normalized as $targetGroup) {
                $this->targets()->updateOrCreate(
                    ['target_group' => $targetGroup],
                    []
                );
            }
        });
    }
}
