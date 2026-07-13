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
        //
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
