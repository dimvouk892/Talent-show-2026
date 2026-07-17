<?php

namespace App\Providers;

use App\Models\Judge;
use App\Models\TalentShow;
use App\Policies\TalentShowPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Hostinger / misconfigured docroot can resolve public_path to .../public/public.
        // Point Vite at the real public folder that contains build/manifest.json.
        $manifest = public_path('build/manifest.json');

        if (is_file($manifest)) {
            return;
        }

        $candidates = [
            base_path('public'),
            dirname(public_path()),
            base_path(),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json')) {
                $this->app->usePublicPath($candidate);

                return;
            }
        }
    }

    public function boot(): void
    {
        Gate::policy(TalentShow::class, TalentShowPolicy::class);

        View::composer('layouts.judge', function ($view) {
            $judge = session('judge_id') ? Judge::find(session('judge_id')) : null;
            $talentShow = session('talent_show_id') ? TalentShow::find(session('talent_show_id')) : null;

            $view->with([
                'layoutJudge' => $judge,
                'layoutTalentShow' => $talentShow,
            ]);
        });
    }
}
