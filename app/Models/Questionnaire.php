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

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'time_limit_minutes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'time_limit_minutes' => 'integer',
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
        $allowedTargetGroups = self::targetGroups();
        $normalized = array_values(array_unique(
            array_filter(
                $targetGroups,
                fn(mixed $value): bool => is_string($value) && in_array($value, $allowedTargetGroups, true)
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

    /**
     * @return array<int, string>
     */
    public static function targetGroups(): array
    {
        $slugs = Role::query()
            ->whereNotIn('id', [1, 2])
            ->whereNotNull('slug')
            ->pluck('slug')
            ->filter(fn(mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn(string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();

        if ($slugs !== []) {
            return $slugs;
        }

        return array_values(array_unique(array_filter(
            (array) config('rbac.questionnaire_target_slugs', []),
            fn(mixed $value): bool => is_string($value) && $value !== ''
        )));
    }

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    public static function targetGroupOptions(): array
    {
        return Role::query()
            ->whereNotIn('id', [1, 2])
            ->whereNotNull('slug')
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(function (Role $role): array {
                return [
                    'slug' => (string) $role->slug,
                    'name' => (string) $role->name,
                ];
            })
            ->filter(fn(array $item): bool => $item['slug'] !== '')
            ->values()
            ->all();
    }
}
