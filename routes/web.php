<?php

use App\Http\Controllers\Admin\QuestionnaireExportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\QuestionnaireAnalytics;
use App\Livewire\Admin\QuestionnaireForm;
use App\Livewire\Admin\QuestionnaireList;
use App\Livewire\Admin\QuestionManager;
use App\Livewire\Admin\UserDirectory;
use App\Livewire\Fill\AvailableQuestionnaires;
use App\Livewire\Fill\ParentDashboard;
use App\Livewire\Fill\QuestionnaireFill;
use App\Livewire\Fill\StaffDashboard;
use App\Livewire\Fill\TeacherDashboard;
use App\Livewire\Shared\ProfilePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('role.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'role.redirect'])->get('/dashboard', function () {
    return response()->noContent();
})->name('role.dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Anda berhasil logout.');
    })->name('logout');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');

    Route::prefix('questionnaires')->name('questionnaires.')->group(function (): void {
        Route::get('/', QuestionnaireList::class)->name('index');
        Route::get('/create', QuestionnaireForm::class)->name('create');
        Route::get('/{questionnaire}', QuestionnaireAnalytics::class)->name('show');
        Route::get('/{questionnaire}/edit', QuestionnaireForm::class)->name('edit');
        Route::get('/{questionnaire}/questions', QuestionManager::class)->name('questions');
    });

    Route::prefix('exports')->name('exports.')->group(function (): void {
        Route::get('/questionnaires-all', [QuestionnaireExportController::class, 'all'])->name('all');
        Route::get('/questionnaires/{questionnaire}', [QuestionnaireExportController::class, 'questionnaire'])->name('questionnaire');
    });

    Route::get('/users', UserDirectory::class)->name('users.index');
    Route::prefix('users')->name('users.')->group(function (): void {
        Route::get('/data', [UserManagementController::class, 'index'])
            ->middleware('throttle:120,1')
            ->name('data');
        Route::get('/{user}', [UserManagementController::class, 'show'])
            ->middleware('throttle:120,1')
            ->name('show');
        Route::post('/', [UserManagementController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('store');
        Route::match(['put', 'patch'], '/{user}', [UserManagementController::class, 'update'])
            ->middleware('throttle:30,1')
            ->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('destroy');
    });
});

Route::middleware(['auth', 'evaluator'])->prefix('fill')->name('fill.')->group(function (): void {
    Route::prefix('dashboard')->name('dashboard.')->group(function (): void {
        Route::get('/guru', TeacherDashboard::class)->name('teacher');
        Route::get('/staff', StaffDashboard::class)->name('staff');
        Route::get('/parent', ParentDashboard::class)->name('parent');
    });

    Route::prefix('questionnaires')->name('questionnaires.')->group(function (): void {
        Route::get('/', AvailableQuestionnaires::class)->name('index');
        Route::get('/{questionnaire}', QuestionnaireFill::class)->name('show');
    });
});
