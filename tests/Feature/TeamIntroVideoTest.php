<?php

namespace Tests\Feature;

use App\Livewire\Admin\ScreenVideos;
use App\Livewire\Presentation\ShowScreen;
use App\Models\Team;
use App\Services\TalentShowControlService;
use App\Services\VoteService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class TeamIntroVideoTest extends TalentShowTestCase
{
    public function test_open_scoring_enables_intro_when_team_has_video(): void
    {
        $team = $this->show->teams()->ordered()->first();
        $team->update(['video_path' => 'teams/'.$this->show->id.'/videos/intro.mp4']);

        app(TalentShowControlService::class)->openScoring($this->show->fresh());

        $this->assertTrue($this->show->fresh()->showing_team_intro);
    }

    public function test_open_scoring_skips_intro_when_team_has_no_video(): void
    {
        app(TalentShowControlService::class)->openScoring($this->show->fresh());

        $this->assertFalse($this->show->fresh()->showing_team_intro);
    }

    public function test_voting_blocked_during_team_intro(): void
    {
        $team = $this->show->teams()->ordered()->first();
        $team->update(['video_path' => 'teams/'.$this->show->id.'/videos/intro.mp4']);

        app(TalentShowControlService::class)->openScoring($this->show->fresh());
        $this->show->refresh();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('intro video');

        app(VoteService::class)->submit(
            $this->show->judges()->first(),
            $this->show->currentTeam,
            8,
        );
    }

    public function test_dismiss_intro_allows_voting(): void
    {
        $team = $this->show->teams()->ordered()->first();
        $team->update(['video_path' => 'teams/'.$this->show->id.'/videos/intro.mp4']);

        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show->fresh());
        $control->dismissTeamIntro($this->show->fresh());

        $currentTeam = $this->show->fresh()->currentTeam;

        $vote = app(VoteService::class)->submit(
            $this->show->judges()->first(),
            $currentTeam,
            8,
        );

        $this->assertEquals(8, $vote->score);
        $this->assertFalse($this->show->fresh()->showing_team_intro);
    }

    public function test_next_team_enables_intro_for_team_with_video(): void
    {
        $teams = $this->show->teams()->ordered()->get();
        $teams[0]->update(['video_path' => 'teams/'.$this->show->id.'/videos/t1.mp4']);
        $teams[1]->update(['video_path' => 'teams/'.$this->show->id.'/videos/t2.mp4']);

        $control = app(TalentShowControlService::class);
        $control->openScoring($this->show->fresh());
        $control->dismissTeamIntro($this->show->fresh());

        $this->show->refresh();

        foreach ($this->show->judges as $judge) {
            app(VoteService::class)->submit($judge, $this->show->currentTeam, 8);
        }

        $control->nextTeam($this->show->fresh());

        $this->assertTrue($this->show->fresh()->showing_team_intro);
        $this->assertEquals($teams[1]->id, $this->show->fresh()->current_team_id);
    }

    public function test_presentation_shows_intro_video_block(): void
    {
        $team = $this->show->teams()->ordered()->first();
        $team->update(['video_path' => 'teams/'.$this->show->id.'/videos/intro.mp4']);

        app(TalentShowControlService::class)->openScoring($this->show->fresh());

        Livewire::test(ShowScreen::class, ['talentShow' => $this->show->fresh()])
            ->assertSee('Intro video')
            ->assertSee($team->name);
    }

    public function test_live_control_can_dismiss_intro(): void
    {
        $team = $this->show->teams()->ordered()->first();
        $team->update(['video_path' => 'teams/'.$this->show->id.'/videos/intro.mp4']);

        app(TalentShowControlService::class)->openScoring($this->show->fresh());

        Livewire::actingAs($this->admin)
            ->test(ScreenVideos::class, ['talentShow' => $this->show->fresh()])
            ->call('dismissTeamIntro')
            ->assertSet('flashSuccess', 'Ξεκίνησε η παρουσίαση της ομάδας.');

        $this->assertFalse($this->show->fresh()->showing_team_intro);
    }

    public function test_admin_can_upload_team_intro_video(): void
    {
        Storage::fake('public');

        $video = UploadedFile::fake()->create('intro.mp4', 1024, 'video/mp4');

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\Teams\Index::class, ['talentShow' => $this->show])
            ->set('name', 'Video Team')
            ->set('display_order', 10)
            ->set('video', $video)
            ->call('save')
            ->assertHasNoErrors();

        $team = Team::where('name', 'Video Team')->first();

        $this->assertNotNull($team->video_path);
        Storage::disk('public')->assertExists($team->video_path);
    }
}
