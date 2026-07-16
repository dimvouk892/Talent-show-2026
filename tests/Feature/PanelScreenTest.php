<?php

namespace Tests\Feature;

use App\Services\VoteService;
use Tests\TalentShowTestCase;

class PanelScreenTest extends TalentShowTestCase
{
    public function test_panel_screen_shows_scoreboard(): void
    {
        $this->openScoring();
        $team = $this->show->currentTeam;
        app(VoteService::class)->submit($this->show->judges()->first(), $team, 12);

        $this->get(route('presentation.panel'))
            ->assertOk()
            ->assertSee('Πίνακας βαθμολογιών')
            ->assertSee($this->show->title)
            ->assertSee($team->name)
            ->assertSee('12');
    }
}
