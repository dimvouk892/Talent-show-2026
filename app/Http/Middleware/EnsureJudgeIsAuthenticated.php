<?php

namespace App\Http\Middleware;

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
        $judge = $this->judgeAccessService->validateSession($request);

        if (! $judge) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Μη εξουσιοδοτημένη πρόσβαση κριτή.'], 401);
            }

            return redirect()->route('judge.access.denied')
                ->with('error', 'Η σύνδεσή σας έληξε ή ανακλήθηκε.');
        }

        $routeJudge = $request->route('judge');

        if ($routeJudge && (int) $routeJudge->id !== $judge->id) {
            return redirect()->route('judge.vote', $judge);
        }

        $request->attributes->set('judge', $judge);

        return $next($request);
    }
}
