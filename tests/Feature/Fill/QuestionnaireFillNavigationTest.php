<?php

namespace Tests\Feature\Fill;

use App\Livewire\Fill\QuestionnaireFill;
use App\Models\Answer;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Support\InteractsWithRoleConfig;

class QuestionnaireFillNavigationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRoleConfig;

    public function test_next_question_moves_with_single_click_flow(): void
    {
        [$user, $questionnaire, $question1, $question2, $option1] = $this->makeQuestionnaireFixture();

        $this->actingAs($user);

        Livewire::test(QuestionnaireFill::class, ['questionnaire' => $questionnaire])
            ->set("answers.{$question1->id}.answer_option_id", $option1->id)
            ->call('nextQuestion')
            ->assertSet('currentIndex', 1)
            ->assertSee($question2->question_text);
    }

    public function test_next_question_persists_current_question_answer(): void
    {
        [$user, $questionnaire, $question1, , $option1] = $this->makeQuestionnaireFixture();

        $this->actingAs($user);

        Livewire::test(QuestionnaireFill::class, ['questionnaire' => $questionnaire])
            ->set("answers.{$question1->id}.answer_option_id", $option1->id)
            ->call('nextQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('answers', [
            'question_id' => $question1->id,
            'answer_option_id' => $option1->id,
        ]);

        $this->assertSame(1, Answer::query()->where('question_id', $question1->id)->count());
    }

    public function test_submit_confirmation_fails_when_single_required_essay_is_empty(): void
    {
        [$user, $questionnaire, $essayQuestion] = $this->makeEssayQuestionnaireFixture(1);
        $this->actingAs($user);

        Livewire::test(QuestionnaireFill::class, ['questionnaire' => $questionnaire])
            ->call('openSubmitConfirmation')
            ->assertHasErrors(["answers.{$essayQuestion->id}.essay_answer"])
            ->assertSet('showSubmitConfirmation', false);
    }

    public function test_submit_confirmation_fails_when_multiple_required_essay_are_empty(): void
    {
        [$user, $questionnaire, $essayQuestions] = $this->makeEssayQuestionnaireFixture(2);
        $this->actingAs($user);

        $component = Livewire::test(QuestionnaireFill::class, ['questionnaire' => $questionnaire])
            ->call('openSubmitConfirmation')
            ->assertSet('showSubmitConfirmation', false);

        foreach ($essayQuestions as $question) {
            $component->assertHasErrors(["answers.{$question->id}.essay_answer"]);
        }
    }

    /**
     * @return array{0: User, 1: Questionnaire, 2: Question, 3: Question, 4: AnswerOption}
     */
    private function makeQuestionnaireFixture(): array
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);
        $user = User::factory()->create(['role' => $this->teacherSlug()]);

        $questionnaire = Questionnaire::query()->create([
            'title' => 'Kuisioner Navigasi',
            'description' => 'Uji klik berikutnya',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        QuestionnaireTarget::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'target_group' => $this->teacherSlug(),
        ]);

        $question1 = Question::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'question_text' => 'Pertanyaan Pertama',
            'type' => 'single_choice',
            'is_required' => true,
            'order' => 1,
        ]);

        $question2 = Question::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'question_text' => 'Pertanyaan Kedua',
            'type' => 'single_choice',
            'is_required' => true,
            'order' => 2,
        ]);

        $option1 = AnswerOption::query()->create([
            'question_id' => $question1->id,
            'option_text' => 'Setuju',
            'score' => 4,
            'order' => 1,
        ]);

        AnswerOption::query()->create([
            'question_id' => $question2->id,
            'option_text' => 'Setuju',
            'score' => 4,
            'order' => 1,
        ]);

        return [$user, $questionnaire, $question1, $question2, $option1];
    }

    /**
     * @return array{0: User, 1: Questionnaire, 2: Question|array<int, Question>}
     */
    private function makeEssayQuestionnaireFixture(int $essayCount): array
    {
        $admin = User::factory()->create(['role' => $this->adminSlug()]);
        $user = User::factory()->create(['role' => $this->teacherSlug()]);

        $questionnaire = Questionnaire::query()->create([
            'title' => 'Kuisioner Essay',
            'description' => 'Uji validasi essay',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        QuestionnaireTarget::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'target_group' => $this->teacherSlug(),
        ]);

        $questions = [];
        for ($i = 1; $i <= $essayCount; $i++) {
            $questions[] = Question::query()->create([
                'questionnaire_id' => $questionnaire->id,
                'question_text' => "Pertanyaan Essay {$i}",
                'type' => 'essay',
                'is_required' => true,
                'order' => $i,
            ]);
        }

        return [$user, $questionnaire, $essayCount === 1 ? $questions[0] : $questions];
    }
}
