<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\Departement;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Database\Seeder;

class BasicTestingSeeder extends Seeder
{
    public function run(): void
    {
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
                'role' => 'admin',
                'department_id' => $depAdministration->id,
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'guru.basic@kepsekeval.test'],
            ['name' => 'Guru Basic', 'role' => 'guru', 'department_id' => $depAcademic->id, 'password' => 'password', 'email_verified_at' => now()]
        );
        User::query()->updateOrCreate(
            ['email' => 'tu.basic@kepsekeval.test'],
            ['name' => 'TU Basic', 'role' => 'tata_usaha', 'department_id' => $depAdministration->id, 'password' => 'password', 'email_verified_at' => now()]
        );
        User::query()->updateOrCreate(
            ['email' => 'ortu.basic@kepsekeval.test'],
            ['name' => 'Orang Tua Basic', 'role' => 'orang_tua', 'department_id' => $depAcademic->id, 'password' => 'password', 'email_verified_at' => now()]
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

        $questionnaire->syncTargetGroups(['guru', 'tata_usaha', 'orang_tua']);

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
