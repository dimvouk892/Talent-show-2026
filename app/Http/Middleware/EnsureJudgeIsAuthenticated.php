<?php

namespace App\Http\Middleware;

use App\Models\Judge;
use App\Services\JudgeAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJudgeIsAuthenticated
{
    public function __construct(
        protected JudgeAccessService $judgeAccessService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $routeJudge = $request->route('judge');
        $expectedJudge = $routeJudge instanceof Judge ? $routeJudge : null;

        $judge = $this->judgeAccessService->validateSession($request, $expectedJudge);

        if (! $judge) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Μη εξουσιοδοτημένη πρόσβαση κριτή.'], 401);
            }

            return redirect()->route('judge.access.denied')
                ->with('error', 'Η σύνδεσή σας έληξε ή ανακλήθηκε.');
        }

        if ($expectedJudge && (int) $expectedJudge->id !== $judge->id) {
            return redirect()->route('judge.access.denied')
                ->with('error', 'Η σύνδεσή σας δεν αντιστοιχεί σε αυτόν τον κριτή.');
        }

        $request->attributes->set('judge', $judge);

        return $next($request);
    }
}
