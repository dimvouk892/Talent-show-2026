<?php

namespace App\Services;

use App\Models\Judge;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function accessUrl(Judge $judge, string $plainToken): string
    {
        return route('judge.access', ['judge' => $judge, 'token' => $plainToken]);
    }

    public function generateSvg(Judge $judge, string $plainToken, int $size = 300): string
    {
        return QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->generate($this->accessUrl($judge, $plainToken));
    }

    public function generatePng(Judge $judge, string $plainToken, int $size = 400): string
    {
        return QrCode::format('png')
            ->size($size)
            ->margin($size >= 300 ? 2 : 1)
            ->generate($this->accessUrl($judge, $plainToken));
    }
}
