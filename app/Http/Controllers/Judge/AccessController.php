<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\Judge;
use App\Services\JudgeAccessService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AccessController extends Controller
{
    public function __construct(
        protected JudgeAccessService $judgeAccessService,
    ) {}

    public function __invoke(Judge $judge, string $token, Request $request)
    {
        try {
            $authenticatedJudge = $this->judgeAccessService->authenticateViaQr($token, $request);

            if ($authenticatedJudge->id !== $judge->id) {
                throw new InvalidArgumentException('Το QR δεν αντιστοιχεί σε αυτόν τον κριτή.');
            }
        } catch (InvalidArgumentException $e) {
            return redirect()->route('judge.access.denied')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('judge.vote', $judge);
    }

    public function denied()
    {
        return view('judge.denied');
    }
}
