<?php

namespace Tests\Feature;

use App\Livewire\Admin\Judges\Index;
use App\Models\Judge;
use Livewire\Livewire;
use Tests\TalentShowTestCase;

class JudgesAdminTest extends TalentShowTestCase
{
    public function test_can_create_judge_with_feedback(): void
    {
        $this->show->judges()->delete();

        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->set('name', 'Νέος Κριτής')
            ->set('title', 'Μουσικός')
            ->call('save')
            ->assertSet('flashSuccess', 'Ο κριτής δημιουργήθηκε.')
            ->assertSee('Ο κριτής δημιουργήθηκε.')
            ->assertSee('Νέος Κριτής');

        $this->assertEquals(1, Judge::where('talent_show_id', $this->show->id)->count());
    }

    public function test_can_create_sixth_active_judge(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->set('name', 'Έκτος Κριτής')
            ->call('save')
            ->assertSet('flashSuccess', 'Ο κριτής δημιουργήθηκε.')
            ->assertSee('Έκτος Κριτής');

        $this->assertEquals(6, Judge::where('talent_show_id', $this->show->id)->count());
    }

    public function test_generate_qr_shows_code_in_judge_card(): void
    {
        $judge = $this->show->judges()->first();

        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->call('generateQr', $judge->id)
            ->assertSet('flashSuccess', 'Δημιουργήθηκε προσωπικό QR για '.$judge->name.'.')
            ->assertSee('Προσωπικό QR — '.$judge->name)
            ->assertSee('/judge/access/'.$judge->id.'/')
            ->assertSee('<svg', false);

        $this->assertTrue($judge->fresh()->hasValidToken());
    }

    public function test_generating_qr_keeps_all_judge_previews(): void
    {
        $judge1 = $this->show->judges()->first();
        $judge2 = $this->show->judges()->skip(1)->first();

        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->call('generateQr', $judge1->id)
            ->assertSee('Προσωπικό QR — '.$judge1->name)
            ->call('generateQr', $judge2->id)
            ->assertSee('Προσωπικό QR — '.$judge1->name)
            ->assertSee('Προσωπικό QR — '.$judge2->name);
    }

    public function test_generate_all_qrs_for_active_judges(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->call('generateAllQrs')
            ->assertSet('flashSuccess', 'Δημιουργήθηκαν QR για 5 κριτές.');

        foreach ($this->show->activeJudges()->get() as $judge) {
            $this->assertTrue($judge->fresh()->hasValidToken());
        }
    }

    public function test_qr_preview_route_returns_png_when_session_has_token(): void
    {
        $judge = $this->show->judges()->first();
        app(\App\Services\JudgeAccessService::class)->generateQrToken($judge);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.judges.qr.preview', $judge));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_qr_codes_visible_after_page_reload_without_session(): void
    {
        $judge = $this->show->judges()->first();
        app(\App\Services\JudgeAccessService::class)->generateQrToken($judge);

        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->assertSee('Προσωπικό QR — '.$judge->name)
            ->assertSee('<svg', false)
            ->assertSee('/judge/access/'.$judge->id.'/');
    }

    public function test_can_edit_judge(): void
    {
        $judge = $this->show->judges()->first();

        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->call('edit', $judge->id)
            ->set('name', 'Ενημερωμένο Όνομα')
            ->call('save')
            ->assertSet('flashSuccess', 'Ο κριτής ενημερώθηκε.')
            ->assertSee('Ενημερωμένο Όνομα');

        $this->assertEquals('Ενημερωμένο Όνομα', $judge->fresh()->name);
    }

    public function test_revoke_qr_via_confirmation_modal(): void
    {
        $judge = $this->show->judges()->first();
        app(\App\Services\JudgeAccessService::class)->generateQrToken($judge);

        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->call('askRevokeQr', $judge->id)
            ->assertSet('confirmRevokeQrId', $judge->id)
            ->call('confirmRevokeQr')
            ->assertSet('flashSuccess', 'Το QR ακυρώθηκε.')
            ->assertSet('confirmRevokeQrId', null);

        $this->assertFalse($judge->fresh()->hasValidToken());
    }

    public function test_revoke_session_via_confirmation_modal(): void
    {
        $judge = $this->show->judges()->first();
        $this->loginJudge($judge);

        Livewire::actingAs($this->admin)
            ->test(Index::class, ['talentShow' => $this->show])
            ->call('askRevokeSession', $judge->id)
            ->assertSet('confirmRevokeSessionId', $judge->id)
            ->call('confirmRevokeSession')
            ->assertSet('flashSuccess', 'Ο κριτής αποσυνδέθηκε.')
            ->assertSet('confirmRevokeSessionId', null);

        $this->assertEquals(0, $judge->activeSessions()->count());
    }
}
