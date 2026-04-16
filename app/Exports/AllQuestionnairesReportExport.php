<?php

namespace App\Exports;

use App\Exports\Sheets\AllQuestionnairesAnswersSheet;
use App\Exports\Sheets\AllQuestionnairesSummarySheet;
use App\Services\QuestionnaireScorer;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllQuestionnairesReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly QuestionnaireScorer $scorer
    ) {
    }

    public function sheets(): array
    {
        return [
            new AllQuestionnairesSummarySheet($this->scorer),
            new AllQuestionnairesAnswersSheet(),
        ];
    }
}
