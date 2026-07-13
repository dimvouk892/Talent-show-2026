<?php

namespace Tests\Feature;

use App\Livewire\Admin\LiveControl;
use App\Livewire\Admin\ScreenVideos;
use App\Livewire\Admin\TalentShows\Edit;
use App\Livewire\Presentation\ShowScreen;
use App\Services\TalentShowControlService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class TalentShowVideoTest extends TalentShowTestCase
{
    public function test_start_show_plays_opening_video_when_configured(): void
    {
        $this->show->update(['opening_video_path' => 'talent-shows/'.$this->show->id.'/videos/opening.mp4']);

        app(TalentShowControlService::class)->startShow($this->show->fresh());

        $this->assertTrue($this->show->fresh()->showing_opening_video);
    }

    public function test_start_show_skips_opening_video_when_not_configured(): void
    {
        app(TalentShowControlService::class)->startShow($this->show->fresh());

        $this->assertFalse($this->show->fresh()->showing_opening_video);
    }

    public function test_complete_show_plays_closing_video_when_configured(): void
    {
        $this->show->update(['closing_video_path' => 'talent-shows/'.$this->show->id.'/videos/closing.mp4']);

        app(TalentShowControlService::class)->completeShow($this->show->fresh());

        $fresh = $this->show->fresh();
        $this->assertTrue($fresh->showing_closing_video);
        $this->assertEquals('completed', $fresh->status->value);
    }

    public function test_open_scoring_dismisses_opening_video(): void
    {
        $this->show->update([
            'opening_video_path' => 'talent-shows/'.$this->show->id.'/videos/opening.mp4',
            'showing_opening_video' => true,
        ]);

        app(TalentShowControlService::class)->openScoring($this->show->fresh());

        $this->assertFalse($this->show->fresh()->showing_opening_video);
    }

    public function test_presentation_shows_opening_video(): void
    {
        $this->show->update([
            'opening_video_path' => 'talent-shows/'.$this->show->id.'/videos/opening.mp4',
            'showing_opening_video' => true,
        ]);

        Livewire::test(ShowScreen::class, ['talentShow' => $this->show->fresh()])
            ->assertSee('Video έναρξης');
    }

    public function test_presentation_shows_closing_video(): void
    {
        $this->show->update([
            'closing_video_path' => 'talent-shows/'.$this->show->id.'/videos/closing.mp4',
            'showing_closing_video' => true,
            'status' => 'completed',
        ]);

        Livewire::test(ShowScreen::class, ['talentShow' => $this->show->fresh()])
            ->assertSee('Video λήξης');
    }

    public function test_live_control_can_dismiss_opening_video(): void
    {
        $this->show->update([
            'opening_video_path' => 'talent-shows/'.$this->show->id.'/videos/opening.mp4',
            'showing_opening_video' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ScreenVideos::class, ['talentShow' => $this->show->fresh()])
            ->call('dismissOpeningVideo')
            ->assertSet('flashSuccess', 'Το video έναρξης ολοκληρώθηκε.');

        $this->assertFalse($this->show->fresh()->showing_opening_video);
    }

    public function test_live_control_can_play_closing_video_anytime(): void
    {
        $this->show->update(['closing_video_path' => 'talent-shows/'.$this->show->id.'/videos/closing.mp4']);

        Livewire::actingAs($this->admin)
            ->test(ScreenVideos::class, ['talentShow' => $this->show->fresh()])
            ->assertSee('Τελικό video')
            ->call('replayClosingVideo')
            ->assertSet('flashSuccess', 'Προβολή τελικού video στην οθόνη.');

        $this->assertTrue($this->show->fresh()->showing_closing_video);
    }

    public function test_live_control_shows_play_buttons(): void
    {
        $this->show->update([
            'opening_video_path' => 'talent-shows/'.$this->show->id.'/videos/opening.mp4',
            'closing_video_path' => 'talent-shows/'.$this->show->id.'/videos/closing.mp4',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ScreenVideos::class, ['talentShow' => $this->show->fresh()])
            ->assertSee('Intro εισαγωγής')
            ->assertSee('Τελικό video')
            ->assertSee('Videos στην οθόνη');
    }

    public function test_screen_videos_page_is_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.talent-shows.screen-videos', $this->show))
            ->assertOk()
            ->assertSee('Videos στην οθόνη');
    }

    public function test_waiting_video_can_be_played_from_screen_videos(): void
    {
        $this->show->update(['waiting_video_path' => 'talent-shows/'.$this->show->id.'/videos/waiting.mp4']);

        Livewire::actingAs($this->admin)
            ->test(ScreenVideos::class, ['talentShow' => $this->show->fresh()])
            ->assertSee('Video αναμονής')
            ->call('replayWaitingVideo')
            ->assertSet('flashSuccess', 'Προβολή video αναμονής στην οθόνη.');

        $this->assertTrue($this->show->fresh()->showing_waiting_video);
    }

    public function test_presentation_shows_waiting_video_when_uploaded(): void
    {
        $this->show->update([
            'waiting_video_path' => 'talent-shows/'.$this->show->id.'/videos/waiting.mp4',
            'showing_waiting_video' => true,
        ]);

        Livewire::test(ShowScreen::class, ['talentShow' => $this->show->fresh()])
            ->assertDontSee('Αναμονή έναρξης...')
            ->assertSee('waiting.mp4', false);
    }

    public function test_uploading_waiting_video_enables_presentation_automatically(): void
    {
        Storage::fake('public');

        $video = UploadedFile::fake()->create('waiting.mp4', 1024, 'video/mp4');

        Livewire::actingAs($this->admin)
            ->test(Edit::class, ['talentShow' => $this->show])
            ->set('waiting_video', $video)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $this->show->fresh();
        $this->assertTrue($fresh->showing_waiting_video);

        Livewire::test(ShowScreen::class, ['talentShow' => $fresh])
            ->assertDontSee('Αναμονή έναρξης...');
    }

    public function test_open_scoring_dismisses_waiting_video(): void
    {
        $this->show->update([
            'waiting_video_path' => 'talent-shows/'.$this->show->id.'/videos/waiting.mp4',
            'showing_waiting_video' => true,
        ]);

        app(TalentShowControlService::class)->openScoring($this->show->fresh());

        $this->assertFalse($this->show->fresh()->showing_waiting_video);
    }

    public function test_waiting_image_can_be_shown_from_screen_videos(): void
    {
        $this->show->update(['waiting_image_path' => 'talent-shows/'.$this->show->id.'/images/waiting.jpg']);

        Livewire::actingAs($this->admin)
            ->test(ScreenVideos::class, ['talentShow' => $this->show->fresh()])
            ->assertSee('Εικόνα αναμονής')
            ->call('showWaitingImage')
            ->assertSet('flashSuccess', 'Προβολή εικόνας αναμονής στην οθόνη.');

        $this->assertTrue($this->show->fresh()->showing_waiting_image);
    }

    public function test_presentation_shows_waiting_image(): void
    {
        $this->show->update([
            'waiting_image_path' => 'talent-shows/'.$this->show->id.'/images/waiting.jpg',
            'showing_waiting_image' => true,
        ]);

        Livewire::test(ShowScreen::class, ['talentShow' => $this->show->fresh()])
            ->assertDontSee('Αναμονή έναρξης...')
            ->assertSee('waiting.jpg', false);
    }

    public function test_clear_scores_restores_waiting_video(): void
    {
        $this->show->update([
            'waiting_video_path' => 'talent-shows/'.$this->show->id.'/videos/waiting.mp4',
            'showing_waiting_video' => true,
        ]);

        $this->openScoring();
        $this->assertFalse($this->show->fresh()->showing_waiting_video);

        app(TalentShowControlService::class)->clearScores($this->show->fresh());

        $this->assertTrue($this->show->fresh()->showing_waiting_video);
    }

    public function test_admin_can_upload_show_videos(): void
    {
        Storage::fake('public');

        $opening = UploadedFile::fake()->create('opening.mp4', 1024, 'video/mp4');
        $closing = UploadedFile::fake()->create('closing.mp4', 1024, 'video/mp4');

        Livewire::actingAs($this->admin)
            ->test(Edit::class, ['talentShow' => $this->show])
            ->set('opening_video', $opening)
            ->set('closing_video', $closing)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $this->show->fresh();
        $this->assertNotNull($fresh->opening_video_path);
        $this->assertNotNull($fresh->closing_video_path);
        Storage::disk('public')->assertExists($fresh->opening_video_path);
        Storage::disk('public')->assertExists($fresh->closing_video_path);
    }
}
