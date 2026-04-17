<?php

namespace Tests\Feature\Admin;

use App\Models\Answer;
use App\Models\Departement;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use App\Services\DepartmentAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_department_analytics_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Analytics');
    }

    public function test_service_calculates_department_metrics_without_duplicate_respondents(): void
    {
        $dep = Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $dep->id]);
        $user = User::factory()->create(['role' => 'guru', 'department_id' => $dep->id, 'is_active' => true]);
        $questionnaire = Questionnaire::factory()->create(['created_by' => $admin->id, 'status' => 'active']);
        $question = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'type' => 'single_choice']);

        $response1 = Response::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'submitted_at' => now()->subDay(),
        ]);

        $response2 = Response::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        Answer::query()->create([
            'response_id' => $response1->id,
            'question_id' => $question->id,
            'department_id' => $dep->id,
            'answer_option_id' => null,
            'essay_answer' => 'A',
            'calculated_score' => 4,
        ]);

        Answer::query()->create([
            'response_id' => $response2->id,
            'question_id' => $question->id,
            'department_id' => $dep->id,
            'answer_option_id' => null,
            'essay_answer' => 'B',
            'calculated_score' => 5,
        ]);

        $rows = app(DepartmentAnalyticsService::class)
            ->summarize(null, null, $dep->id, 'name', 'asc', 10, 1)['rows'];

        $row = $rows->items()[0];

        $this->assertSame(1, (int) $row->total_respondents);
        $this->assertSame(100.0, (float) $row->participation_rate);
        $this->assertSame(4.5, (float) $row->average_score);
    }

    public function test_service_handles_large_dataset_10000_answers(): void
    {
        $dep = Departement::query()->create(['name' => 'Kurikulum', 'urut' => 1]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $dep->id]);
        $questionnaire = Questionnaire::factory()->create(['created_by' => $admin->id, 'status' => 'active']);
        $users = User::factory()->count(200)->create([
            'role' => 'guru',
            'department_id' => $dep->id,
            'is_active' => true,
        ]);

        $questions = Question::factory()->count(50)->create([
            'questionnaire_id' => $questionnaire->id,
            'type' => 'single_choice',
        ]);

        $responseIds = [];
        foreach ($users as $u) {
            $response = Response::query()->create([
                'questionnaire_id' => $questionnaire->id,
                'user_id' => $u->id,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            $responseIds[] = $response->id;
        }

        $rows = [];
        foreach ($responseIds as $responseId) {
            foreach ($questions as $index => $q) {
                $rows[] = [
                    'response_id' => $responseId,
                    'question_id' => $q->id,
                    'department_id' => $dep->id,
                    'answer_option_id' => null,
                    'essay_answer' => null,
                    'calculated_score' => 3 + ($index % 3),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Answer::query()->insert($rows);

        $summary = app(DepartmentAnalyticsService::class)->summarize(null, null, null, 'name', 'asc', 10, 1);
        $this->assertNotEmpty($summary['rows']->items());
    }
}
