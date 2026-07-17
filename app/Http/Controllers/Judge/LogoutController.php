<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\Judge;
use App\Services\JudgeAccessService;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        protected JudgeAccessService $judgeAccessService,
    ) {}

    public function __invoke(Request $request, Judge $judge)
    {
        $this->judgeAccessService->logout($request, $judge);

        return redirect()->route('judge.access.denied')
            ->with('success', 'Αποσυνδεθήκατε επιτυχώς.');
    }
}
