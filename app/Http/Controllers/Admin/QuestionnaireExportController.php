<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AllQuestionnairesReportExport;
use App\Exports\QuestionnaireReportExport;
use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Services\QuestionnaireScorer;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestionnaireExportController extends Controller
{
    public function questionnaire(Questionnaire $questionnaire): BinaryFileResponse
    {
        $this->authorize('view', $questionnaire);

        $filename = 'questionnaire_'.$questionnaire->id.'_report_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new QuestionnaireReportExport($questionnaire, app(QuestionnaireScorer::class)),
            $filename
        );
    }

    public function all(): BinaryFileResponse
    {
        $this->authorize('viewAny', Questionnaire::class);

        $filename = 'all_questionnaires_report_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new AllQuestionnairesReportExport(app(QuestionnaireScorer::class)),
            $filename
        );
    }
}
