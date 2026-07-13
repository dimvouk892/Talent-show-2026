<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Judge;
use App\Services\JudgeAccessService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QrCodeController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService,
        protected JudgeAccessService $judgeAccessService,
    ) {}

    protected function resolvePlainToken(Judge $judge, Request $request): ?string
    {
        return $request->session()->get('qr_token_'.$judge->id)
            ?? $judge->plainAccessToken();
    }

    public function download(Judge $judge, Request $request): Response
    {
        $this->authorize('update', $judge->talentShow);

        $plainToken = $this->resolvePlainToken($judge, $request);

        if (! $plainToken) {
            abort(404, 'Το QR token δεν είναι διαθέσιμο. Δημιουργήστε νέο QR.');
        }

        $png = $this->qrCodeService->generatePng($judge, $plainToken);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="judge-'.$judge->id.'-qr.png"',
        ]);
    }

    public function print(Judge $judge, Request $request)
    {
        $this->authorize('update', $judge->talentShow);

        $plainToken = $this->resolvePlainToken($judge, $request);

        if (! $plainToken) {
            abort(404, 'Το QR token δεν είναι διαθέσιμο.');
        }

        $svg = $this->qrCodeService->generateSvg($judge, $plainToken);

        return view('admin.judges.print-qr', [
            'judge' => $judge,
            'svg' => $svg,
            'url' => $this->qrCodeService->accessUrl($judge, $plainToken),
        ]);
    }

    public function preview(Judge $judge, Request $request): Response
    {
        $this->authorize('view', $judge->talentShow);

        $plainToken = $this->resolvePlainToken($judge, $request);

        if (! $plainToken) {
            abort(404);
        }

        return response($this->qrCodeService->generatePng($judge, $plainToken, 140), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store',
        ]);
    }
}
