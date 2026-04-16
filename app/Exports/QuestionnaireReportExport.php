<?php

namespace App\Exports;

use App\Exports\Sheets\QuestionnaireAnswersSheet;
use App\Exports\Sheets\QuestionnaireSummarySheet;
use App\Models\Questionnaire;
use App\Services\QuestionnaireScorer;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QuestionnaireReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Questionnaire $questionnaire,
        private readonly QuestionnaireScorer $scorer
    ) {
    }

    public function sheets(): array
    {
        $analytics = $this->scorer->summarizeQuestionnaire($this->questionnaire);

        return [
            new QuestionnaireSummarySheet($this->questionnaire, $analytics),
            new QuestionnaireAnswersSheet($this->questionnaire),
        ];
    }
}
