<?php

use App\Http\Controllers\Admin\QrCodeController;
use App\Http\Controllers\Admin\ResultsExportController;
use App\Http\Controllers\Judge\AccessController;
use App\Http\Controllers\Judge\LogoutController;
use App\Livewire\Admin\AuditLogs;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Judges\Index as JudgesIndex;
use App\Livewire\Admin\LiveControl;
use App\Livewire\Admin\Login;
use App\Livewire\Admin\Results;
use App\Livewire\Admin\TalentShows\Create as TalentShowCreate;
use App\Livewire\Admin\TalentShows\Edit as TalentShowEdit;
use App\Livewire\Admin\TalentShows\Index as TalentShowsIndex;
use App\Livewire\Admin\TalentShows\Show as TalentShowShow;
use App\Livewire\Admin\Teams\Index as TeamsIndex;
use App\Livewire\Judge\VotePanel;
use App\Livewire\Presentation\PanelScreen;
use App\Livewire\Presentation\RankingScreen;
use App\Livewire\Presentation\ShowScreen;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/judge/access/denied', [AccessController::class, 'denied'])->name('judge.access.denied');

Route::get('/judge/access/{judge}/{token}', AccessController::class)
    ->middleware('throttle:10,1')
    ->name('judge.access');

Route::middleware(['judge.auth'])->group(function () {
    Route::get('/judge/{judge}/vote', VotePanel::class)->name('judge.vote');
    Route::get('/judge/vote', function () {
        $judgeId = session('judge_id');

        if (! $judgeId) {
            return redirect()->route('judge.access.denied');
        }

        return redirect()->route('judge.vote', ['judge' => $judgeId]);
    });
    Route::post('/judge/logout', LogoutController::class)->name('judge.logout');
});

Route::get('/monitor', ShowScreen::class)->name('presentation.show');
Route::get('/monitor/panel', PanelScreen::class)->name('presentation.panel');
Route::get('/monitor/ranking', RankingScreen::class)->name('presentation.ranking');
Route::redirect('/monitor/winner', '/monitor')->name('presentation.winner');

Route::redirect('/presentation/{talentShow}', '/monitor');
Route::redirect('/presentation/{talentShow}/ranking', '/monitor/ranking');
Route::redirect('/presentation/{talentShow}/winner', '/monitor');
Route::redirect('/panel', '/monitor/panel');

Route::prefix('admin')->group(function () {
    Route::get('/login', Login::class)->name('admin.login');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->middleware('auth')->name('admin.logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', Dashboard::class)->name('admin.dashboard');
        Route::get('/talent-shows', TalentShowsIndex::class)->name('admin.talent-shows.index');
        Route::get('/talent-shows/create', TalentShowCreate::class)->name('admin.talent-shows.create');
        Route::get('/talent-shows/{talentShow}', TalentShowShow::class)->name('admin.talent-shows.show');
        Route::get('/talent-shows/{talentShow}/edit', TalentShowEdit::class)->name('admin.talent-shows.edit');
        Route::get('/talent-shows/{talentShow}/teams', TeamsIndex::class)->name('admin.talent-shows.teams');
        Route::get('/talent-shows/{talentShow}/judges', JudgesIndex::class)->name('admin.talent-shows.judges');
        Route::get('/talent-shows/{talentShow}/live-control', LiveControl::class)->name('admin.talent-shows.live-control');
        Route::get('/talent-shows/{talentShow}/results', Results::class)->name('admin.talent-shows.results');
        Route::get('/talent-shows/{talentShow}/results/print', [ResultsExportController::class, 'print'])->name('admin.talent-shows.results.print');
        Route::get('/talent-shows/{talentShow}/results/export', [ResultsExportController::class, 'csv'])->name('admin.talent-shows.results.export');
        Route::get('/talent-shows/{talentShow}/audit-logs', AuditLogs::class)->name('admin.talent-shows.audit-logs');
        Route::get('/judges/{judge}/qr/download', [QrCodeController::class, 'download'])->name('admin.judges.qr.download');
        Route::get('/judges/{judge}/qr/preview', [QrCodeController::class, 'preview'])->name('admin.judges.qr.preview');
        Route::get('/judges/{judge}/qr/print', [QrCodeController::class, 'print'])->name('admin.judges.qr.print');
    });
});
