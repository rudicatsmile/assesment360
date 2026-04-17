<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Departement;
use App\Models\Role;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Database\Seeder;

class BasicTestingSeeder extends Seeder
{
    public function run(): void
    {
        $adminSlug = (string) ((array) config('rbac.admin_slugs', []))[0];
        $teacherSlug = (string) config('rbac.dashboard_role_slugs.teacher');
        $staffSlug = (string) config('rbac.dashboard_role_slugs.staff');
        $parentSlug = (string) config('rbac.dashboard_role_slugs.parent');
        $targetGroups = array_values(array_unique(array_filter((array) config('rbac.questionnaire_target_slugs', []))));

        $roleAdmin = Role::query()->where('slug', $adminSlug)->first();
        $roleGuru = Role::query()->where('slug', $teacherSlug)->first();
        $roleTu = Role::query()->where('slug', $staffSlug)->first();
        $roleParent = Role::query()->where('slug', $parentSlug)->first();

        $depAcademic = Departement::query()->updateOrCreate(
            ['name' => 'Akademik'],
            ['urut' => 1, 'description' => 'Urusan akademik']
        );
        $depAdministration = Departement::query()->updateOrCreate(
            ['name' => 'Administrasi'],
            ['urut' => 2, 'description' => 'Urusan administrasi']
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin.basic@kepsekeval.test'],
            [
                'name' => 'Admin Basic',
                'role' => $adminSlug,
                'role_id' => $roleAdmin?->id,
                'department_id' => $depAdministration->id,
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'guru.basic@kepsekeval.test'],
            ['name' => 'Guru Basic', 'role' => $teacherSlug, 'role_id' => $roleGuru?->id, 'department_id' => $depAcademic->id, 'password' => 'password', 'email_verified_at' => now()]
        );
        User::query()->updateOrCreate(
            ['email' => 'tu.basic@kepsekeval.test'],
            ['name' => 'TU Basic', 'role' => $staffSlug, 'role_id' => $roleTu?->id, 'department_id' => $depAdministration->id, 'password' => 'password', 'email_verified_at' => now()]
        );
        User::query()->updateOrCreate(
            ['email' => 'ortu.basic@kepsekeval.test'],
            ['name' => 'Orang Tua Basic', 'role' => $parentSlug, 'role_id' => $roleParent?->id, 'department_id' => $depAcademic->id, 'password' => 'password', 'email_verified_at' => now()]
        );

        $questionnaire = Questionnaire::query()->updateOrCreate(
            ['title' => 'Basic Testing - Evaluasi Kepsek'],
            [
                'description' => 'Kuisioner contoh dasar untuk testing cepat.',
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(14),
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $questionnaire->syncTargetGroups($targetGroups);

        $question = Question::query()->updateOrCreate(
            [
                'questionnaire_id' => $questionnaire->id,
                'order' => 1,
            ],
            [
                'question_text' => 'Kepala sekolah memberikan arahan yang jelas.',
                'type' => 'single_choice',
                'is_required' => true,
            ]
        );

        $options = [
            ['option_text' => 'Sangat Setuju', 'score' => 5, 'order' => 1],
            ['option_text' => 'Setuju', 'score' => 4, 'order' => 2],
            ['option_text' => 'Netral', 'score' => 3, 'order' => 3],
            ['option_text' => 'Tidak Setuju', 'score' => 2, 'order' => 4],
            ['option_text' => 'Sangat Tidak Setuju', 'score' => 1, 'order' => 5],
        ];

        foreach ($options as $option) {
            AnswerOption::query()->updateOrCreate(
                [
                    'question_id' => $question->id,
                    'order' => $option['order'],
                ],
                $option
            );
        }
    }
}
