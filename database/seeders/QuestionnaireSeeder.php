<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireTarget;
use App\Models\Response;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $dashboardRoleSlugs = (array) config('rbac.dashboard_role_slugs', []);
        $adminSlug = (string) ((array) config('rbac.admin_slugs', []))[0];
        $teacherSlug = (string) ($dashboardRoleSlugs['teacher'] ?? '');
        $staffSlug = (string) ($dashboardRoleSlugs['staff'] ?? '');
        $parentSlug = (string) ($dashboardRoleSlugs['parent'] ?? '');
        $targetGroups = array_values(array_unique(array_filter((array) config('rbac.questionnaire_target_slugs', []))));

        $users = collect([
            ['name' => 'Admin Kepsek', 'email' => 'admin@kepsekeval.test', 'role' => $adminSlug],
            ['name' => 'Guru Contoh 1', 'email' => 'guru1@kepsekeval.test', 'role' => $teacherSlug],
            ['name' => 'Guru Contoh 2', 'email' => 'guru2@kepsekeval.test', 'role' => $teacherSlug],
            ['name' => 'Tata Usaha Contoh', 'email' => 'tu@kepsekeval.test', 'role' => $staffSlug],
            ['name' => 'Orang Tua Contoh', 'email' => 'orangtua@kepsekeval.test', 'role' => $parentSlug],
        ])->mapWithKeys(function (array $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'role' => $userData['role'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            return [$userData['email'] => $user];
        });

        $admin = $users['admin@kepsekeval.test'];

        $questionnaireDefinitions = [
            [
                'title' => 'Penilaian Kinerja Kepala Sekolah Semester Ganjil',
                'description' => 'Evaluasi layanan dan kepemimpinan kepala sekolah pada semester ganjil.',
                'start_date' => now()->subDays(14),
                'end_date' => now()->addDays(14),
                'status' => 'active',
            ],
            [
                'title' => 'Evaluasi Program Akademik 2026',
                'description' => 'Penilaian pelaksanaan program akademik dan dukungan kepala sekolah.',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(7),
                'status' => 'active',
            ],
            [
                'title' => 'Survei Layanan Administrasi Sekolah',
                'description' => 'Survei untuk melihat kualitas koordinasi administrasi dan keputusan pimpinan.',
                'start_date' => now()->subDays(45),
                'end_date' => now()->subDays(5),
                'status' => 'closed',
            ],
            [
                'title' => 'Draft Penilaian Kepemimpinan 2027',
                'description' => 'Draft kuisioner persiapan evaluasi tahun ajaran berikutnya.',
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(40),
                'status' => 'draft',
            ],
            [
                'title' => 'Monitoring Komunikasi dan Kolaborasi',
                'description' => 'Evaluasi efektivitas komunikasi kepala sekolah dengan seluruh pemangku kepentingan.',
                'start_date' => now()->subDays(3),
                'end_date' => now()->addDays(21),
                'status' => 'active',
            ],
        ];

        $questionTemplates = [
            [
                'question_text' => 'Bagaimana tingkat kepuasan Anda terhadap kepemimpinan kepala sekolah?',
                'type' => 'single_choice',
                'is_required' => true,
                'order' => 1,
            ],
            [
                'question_text' => 'Apakah kepala sekolah memberikan dukungan yang cukup dalam pengembangan profesional?',
                'type' => 'single_choice',
                'is_required' => true,
                'order' => 2,
            ],
            [
                'question_text' => 'Bagaimana pendapat Anda tentang komunikasi yang dilakukan kepala sekolah?',
                'type' => 'combined',
                'is_required' => true,
                'order' => 3,
            ],
            [
                'question_text' => 'Tuliskan masukan atau pengalaman Anda terkait kepemimpinan kepala sekolah.',
                'type' => 'essay',
                'is_required' => false,
                'order' => 4,
            ],
            [
                'question_text' => 'Apakah kepala sekolah responsif terhadap masukan dari warga sekolah?',
                'type' => 'single_choice',
                'is_required' => true,
                'order' => 5,
            ],
        ];

        $optionTemplates = [
            ['option_text' => 'Sangat Setuju', 'score' => 5, 'order' => 1],
            ['option_text' => 'Setuju', 'score' => 4, 'order' => 2],
            ['option_text' => 'Netral', 'score' => 3, 'order' => 3],
            ['option_text' => 'Tidak Setuju', 'score' => 2, 'order' => 4],
            ['option_text' => 'Sangat Tidak Setuju', 'score' => 1, 'order' => 5],
        ];

        $questionnaires = collect($questionnaireDefinitions)->map(function (array $definition) use ($admin, $questionTemplates, $optionTemplates, $targetGroups) {
            $questionnaire = Questionnaire::updateOrCreate(
                ['title' => $definition['title']],
                $definition + ['created_by' => $admin->id],
            );

            foreach ($targetGroups as $targetGroup) {
                QuestionnaireTarget::updateOrCreate(
                    [
                        'questionnaire_id' => $questionnaire->id,
                        'target_group' => $targetGroup,
                    ],
                    [],
                );
            }

            foreach ($questionTemplates as $questionTemplate) {
                $question = Question::updateOrCreate(
                    [
                        'questionnaire_id' => $questionnaire->id,
                        'order' => $questionTemplate['order'],
                    ],
                    $questionTemplate,
                );

                if (! in_array($question->type, ['single_choice', 'combined'], true)) {
                    continue;
                }

                foreach ($optionTemplates as $optionTemplate) {
                    AnswerOption::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'order' => $optionTemplate['order'],
                        ],
                        $optionTemplate,
                    );
                }
            }

            return $questionnaire;
        });

        $responseBlueprints = [
            ['questionnaire' => 0, 'email' => 'guru1@kepsekeval.test', 'status' => 'submitted', 'submitted_at' => now()->subDays(2)],
            ['questionnaire' => 0, 'email' => 'guru2@kepsekeval.test', 'status' => 'submitted', 'submitted_at' => now()->subDay()],
            ['questionnaire' => 0, 'email' => 'tu@kepsekeval.test', 'status' => 'submitted', 'submitted_at' => now()->subHours(10)],
            ['questionnaire' => 0, 'email' => 'orangtua@kepsekeval.test', 'status' => 'submitted', 'submitted_at' => now()->subHours(6)],
            ['questionnaire' => 1, 'email' => 'guru1@kepsekeval.test', 'status' => 'draft', 'submitted_at' => null],
        ];

        $essayByRole = [
            $teacherSlug => 'Kepala sekolah terbuka terhadap ide pembelajaran baru dan cukup aktif memberi arahan.',
            $staffSlug => 'Koordinasi administrasi berjalan baik dan keputusan biasanya jelas.',
            $parentSlug => 'Komunikasi dengan orang tua sudah baik dan informasinya mudah dipahami.',
        ];

        $combinedEssayByRole = [
            $teacherSlug => 'Komunikasi berjalan dua arah dan tindak lanjut biasanya cepat.',
            $staffSlug => 'Arahan kerja mudah dipahami serta membantu proses administrasi.',
            $parentSlug => 'Informasi sekolah cukup rutin dan membantu kami mengikuti perkembangan.',
        ];

        foreach ($responseBlueprints as $index => $blueprint) {
            $questionnaire = $questionnaires[$blueprint['questionnaire']];
            $respondent = $users[$blueprint['email']];

            $response = Response::updateOrCreate(
                [
                    'questionnaire_id' => $questionnaire->id,
                    'user_id' => $respondent->id,
                ],
                [
                    'status' => $blueprint['status'],
                    'submitted_at' => $blueprint['submitted_at'],
                ],
            );

            $selectedScores = [
                0 => [5, 4, 5, null, 4],
                1 => [4, 4, 4, null, 4],
                2 => [4, 5, 4, null, 5],
                3 => [5, 4, 5, null, 5],
                4 => [null, null, null, null, null],
            ][$index];

            foreach ($questionnaire->questions as $position => $question) {
                $selectedScore = $selectedScores[$position];
                $answerOption = null;
                $essayAnswer = null;
                $calculatedScore = null;

                if (in_array($question->type, ['single_choice', 'combined'], true) && $selectedScore !== null) {
                    $answerOption = $question->answerOptions()->where('score', $selectedScore)->first();
                    $calculatedScore = $answerOption?->score;
                }

                if ($question->type === 'essay') {
                    $essayAnswer = (string) ($essayByRole[$respondent->role] ?? 'Catatan evaluasi umum.');
                }

                if ($question->type === 'combined') {
                    $essayAnswer = (string) ($combinedEssayByRole[$respondent->role] ?? 'Komunikasi cukup baik.');
                }

                Answer::updateOrCreate(
                    [
                        'response_id' => $response->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'answer_option_id' => $answerOption?->id,
                        'essay_answer' => $essayAnswer,
                        'calculated_score' => $calculatedScore,
                    ],
                );
            }
        }

        $this->command->info('QuestionnaireSeeder berhasil dijalankan dengan data minimal 5 baris per tabel utama.');
        $this->command->info('Akun admin: admin@kepsekeval.test / password');
        $this->command->info('Akun guru: guru1@kepsekeval.test / password');
        $this->command->info('Akun guru: guru2@kepsekeval.test / password');
        $this->command->info('Akun tata usaha: tu@kepsekeval.test / password');
        $this->command->info('Akun orang tua: orangtua@kepsekeval.test / password');
    }
}
