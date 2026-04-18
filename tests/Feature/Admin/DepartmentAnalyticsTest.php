<?php

namespace Tests\Feature\Admin;

use App\Models\Answer;
use App\Models\Departement;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\Response;
use App\Models\User;
use App\Services\DepartmentAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class DepartmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    public function test_admin_can_open_department_analytics_page(): void
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);
        $this->actingAs($admin);

        $this->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Analytics');
    }

    public function test_service_calculates_department_metrics_without_duplicate_respondents(): void
    {
        $dep = Departement::query()->create(['name' => 'Akademik', 'urut' => 1]);
        $admin = User::factory()->create(['role' => $this->adminSlug(), 'department_id' => $dep->id]);
        $user = User::factory()->create(['role' => $this->teacherSlug(), 'department_id' => $dep->id, 'is_active' => true]);
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
        $admin = User::factory()->create(['role' => $this->adminSlug(), 'department_id' => $dep->id]);
        $questionnaire = Questionnaire::factory()->create(['created_by' => $admin->id, 'status' => 'active']);
        $users = User::factory()->count(200)->create([
            'role' => $this->teacherSlug(),
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

    public function test_service_can_summarize_roles_for_selected_department(): void
    {
        $dep = Departement::query()->create(['name' => 'Kesiswaan', 'urut' => 2]);
        $roleA = Role::query()->create(['name' => 'Pengurus Yayasan', 'slug' => 'pengurus_yayasan', 'prosentase' => 80, 'is_active' => true]);
        $roleB = Role::query()->create(['name' => 'Guru Staf', 'slug' => 'guru_staf', 'prosentase' => 70, 'is_active' => true]);

        $userRoleA1 = User::factory()->create([
            'department_id' => $dep->id,
            'role_id' => $roleA->id,
            'role' => $roleA->slug,
            'is_active' => true,
        ]);
        $userRoleA2 = User::factory()->create([
            'department_id' => $dep->id,
            'role_id' => $roleA->id,
            'role' => $roleA->slug,
            'is_active' => true,
        ]);
        $userRoleB1 = User::factory()->create([
            'department_id' => $dep->id,
            'role_id' => $roleB->id,
            'role' => $roleB->slug,
            'is_active' => true,
        ]);

        $questionnaire = Questionnaire::factory()->create(['status' => 'active', 'created_by' => $userRoleA1->id]);
        $question = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'type' => 'single_choice']);

        $responseA1 = Response::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $userRoleA1->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $responseB1 = Response::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $userRoleB1->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        Answer::query()->create([
            'response_id' => $responseA1->id,
            'question_id' => $question->id,
            'department_id' => $dep->id,
            'calculated_score' => 4.2,
        ]);
        Answer::query()->create([
            'response_id' => $responseB1->id,
            'question_id' => $question->id,
            'department_id' => $dep->id,
            'calculated_score' => 3.5,
        ]);

        $summary = app(DepartmentAnalyticsService::class)->summarizeRolesByDepartment($dep->id);
        $rows = collect($summary['rows'])->keyBy('role_name');

        $this->assertSame('Kesiswaan', $summary['department_name']);
        $this->assertSame(2, (int) $rows['Pengurus Yayasan']['total_respondents']);
        $this->assertSame(50.0, (float) $rows['Pengurus Yayasan']['participation_rate']);
        $this->assertSame(4.2, (float) $rows['Pengurus Yayasan']['average_score']);
        $this->assertSame(1, (int) $rows['Guru Staf']['total_respondents']);
        $this->assertSame(100.0, (float) $rows['Guru Staf']['participation_rate']);
        $this->assertSame(3.5, (float) $rows['Guru Staf']['average_score']);
    }

    public function test_service_can_summarize_users_for_department_role(): void
    {
        $dep = Departement::query()->create(['name' => 'Sarana', 'urut' => 3]);
        $role = Role::query()->create(['name' => 'Komite', 'slug' => 'komite', 'prosentase' => 60, 'is_active' => true]);
        $userA = User::factory()->create([
            'department_id' => $dep->id,
            'role_id' => $role->id,
            'role' => $role->slug,
            'name' => 'Ahmad Komite',
            'is_active' => true,
        ]);
        $userB = User::factory()->create([
            'department_id' => $dep->id,
            'role_id' => $role->id,
            'role' => $role->slug,
            'name' => 'Budi Komite',
            'is_active' => true,
        ]);

        $questionnaire = Questionnaire::factory()->create(['status' => 'active', 'created_by' => $userA->id]);
        $question = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'type' => 'single_choice']);

        $responseA = Response::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $userA->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $responseB = Response::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $userB->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        Answer::query()->create([
            'response_id' => $responseA->id,
            'question_id' => $question->id,
            'department_id' => $dep->id,
            'calculated_score' => 4.7,
        ]);
        Answer::query()->create([
            'response_id' => $responseB->id,
            'question_id' => $question->id,
            'department_id' => $dep->id,
            'calculated_score' => 3.9,
        ]);

        $users = app(DepartmentAnalyticsService::class)->summarizeUsersByDepartmentRole($dep->id, $role->id);
        $first = collect($users)->firstWhere('user_name', 'Ahmad Komite');

        $this->assertNotEmpty($users);
        $this->assertSame(1, (int) ($first['total_submissions'] ?? 0));
        $this->assertSame(4.7, (float) ($first['average_score'] ?? 0));
    }
}
