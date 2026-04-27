<?php

use App\Http\Controllers\Admin\QuestionnaireExportController;
use App\Http\Controllers\Admin\DepartmentAnalyticsExportController;
use App\Http\Controllers\Admin\DepartmentManagementController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\WhatsAppWebhookController;
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\DepartmentAnalytics;
use App\Livewire\Admin\QuestionnaireAnalytics;
use App\Livewire\Admin\QuestionnaireForm;
use App\Livewire\Admin\QuestionnaireList;
use App\Livewire\Admin\QuestionManager;
use App\Livewire\Admin\DepartmentDirectory;
use App\Livewire\Admin\RoleDirectory;
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

$adminGateMiddleware = (string) config('rbac.middleware_aliases.admin_gate', 'access.admin');
$evaluatorGateMiddleware = (string) config('rbac.middleware_aliases.evaluator_gate', 'access.evaluator');
$roleRedirectMiddleware = (string) config('rbac.middleware_aliases.role_redirect', 'access.role.redirect');
$adminRoutePrefix = (string) config('rbac.admin_route.prefix', '');
$adminRouteName = (string) config('rbac.admin_route.name', 'admin.');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('role.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login/yadkhul', [AuthController::class, 'showAdminLogin'])->name('login.admin');
    Route::post('/login', [AuthController::class, 'loginWithPassword'])
        ->middleware('throttle:20,1')
        ->name('login.attempt');
    Route::post('/login/yadkhul', [AuthController::class, 'adminLoginWithPassword'])
        ->middleware('throttle:20,1')
        ->name('login.admin.attempt');
    Route::post('/login/send-verification', [AuthController::class, 'sendVerification'])
        ->middleware('throttle:10,1')
        ->name('login.send_verification');
    Route::post('/login/verify-code', [AuthController::class, 'verifyCode'])
        ->middleware('throttle:15,1')
        ->name('login.verify_code');
});

Route::match(['get', 'post'], '/webhooks/whatsapp', WhatsAppWebhookController::class)
    ->name('webhooks.whatsapp');

Route::middleware(['auth', $roleRedirectMiddleware])->get('/dashboard', function () {
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

Route::middleware(['auth', $adminGateMiddleware])->prefix($adminRoutePrefix)->name($adminRouteName)->group(function () use ($adminRoutePrefix): void {
    Route::redirect('/', '/' . trim($adminRoutePrefix, '/') . '/dashboard');
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('/analytics', DepartmentAnalytics::class)->name('analytics.index');

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
        Route::get('/department-analytics/excel', [DepartmentAnalyticsExportController::class, 'excel'])->name('department-analytics.excel');
        Route::get('/department-analytics/pdf', [DepartmentAnalyticsExportController::class, 'pdf'])->name('department-analytics.pdf');
    });

    Route::get('/users', UserDirectory::class)->name('users.index');
    Route::get('/roles', RoleDirectory::class)->name('roles.index');
    Route::get('/departments', DepartmentDirectory::class)->name('departments.index');
    Route::prefix('departments')->name('departments.')->group(function (): void {
        Route::get('/data', [DepartmentManagementController::class, 'index'])
            ->middleware('throttle:120,1')
            ->name('data');
        Route::get('/{departement}', [DepartmentManagementController::class, 'show'])
            ->middleware('throttle:120,1')
            ->name('show');
        Route::post('/', [DepartmentManagementController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('store');
        Route::match(['put', 'patch'], '/{departement}', [DepartmentManagementController::class, 'update'])
            ->middleware('throttle:30,1')
            ->name('update');
        Route::delete('/{departement}', [DepartmentManagementController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('destroy');
    });

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

    Route::prefix('roles')->name('roles.')->group(function (): void {
        Route::get('/data', [RoleController::class, 'index'])
            ->middleware('throttle:120,1')
            ->name('data');
        Route::post('/', [RoleController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('store');
        Route::match(['put', 'patch'], '/{role}', [RoleController::class, 'update'])
            ->middleware('throttle:30,1')
            ->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('destroy');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    });
});

Route::middleware(['auth', $evaluatorGateMiddleware])->prefix('fill')->name('fill.')->group(function (): void {
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
