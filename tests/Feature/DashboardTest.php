<?php

namespace Tests\Feature;

use App\Enums\ChallengeStatus;
use App\Enums\SubmissionStatus;
use App\Models\Challenge;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_guests_are_redirected_to_the_pin_screen(): void
    {
        $this->get('/dashboard')->assertRedirect(route('dashboard.pin'));
    }

    public function test_a_wrong_pin_is_rejected(): void
    {
        config(['services.signal.dashboard_pin' => 'secret']);

        $this->post('/dashboard/pin', ['pin' => 'wrong'])->assertSessionHasErrors('pin');
    }

    public function test_the_correct_pin_unlocks_the_dashboard(): void
    {
        config(['services.signal.dashboard_pin' => 'secret']);

        $this->post('/dashboard/pin', ['pin' => 'secret'])->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertOk();
    }

    public function test_dashboard_shows_team_scores_and_challenges(): void
    {
        $this->withSession(['dashboard_authenticated' => true]);

        Team::factory()->create(['name' => 'Rood']);
        Challenge::factory()->create(['number' => 3, 'title' => 'Bergtop selfie']);

        Livewire::test('dashboard')
            ->assertSee('Rood')
            ->assertSee('Bergtop selfie');
    }

    public function test_approving_a_pending_submission_from_the_dashboard_awards_points(): void
    {
        $this->withSession(['dashboard_authenticated' => true]);
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->released()->create(['points' => 10]);
        $submission = Submission::factory()->for($challenge)->for($team)->create();

        Livewire::test('dashboard')->call('approve', $submission->id);

        $this->assertSame(SubmissionStatus::Approved, $submission->fresh()->status);
        $this->assertSame('dashboard', $submission->fresh()->approved_by);
    }

    public function test_releasing_a_draft_challenge_from_the_dashboard_marks_it_released(): void
    {
        $this->withSession(['dashboard_authenticated' => true]);
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        Team::factory()->create();
        $challenge = Challenge::factory()->create(['status' => ChallengeStatus::Draft]);

        Livewire::test('dashboard')->call('release', $challenge->id);

        $this->assertSame(ChallengeStatus::Released, $challenge->fresh()->status);
    }

    public function test_releasing_a_challenge_still_succeeds_when_a_signal_message_fails(): void
    {
        $this->withSession(['dashboard_authenticated' => true]);
        Http::fake(['*' => Http::response('server error', 500)]);

        Team::factory()->create(['name' => 'Rood', 'signal_group_id' => 'group-1']);
        $challenge = Challenge::factory()->create(['status' => ChallengeStatus::Draft]);

        Livewire::test('dashboard')
            ->call('release', $challenge->id)
            ->assertSee('Rood');

        $this->assertSame(ChallengeStatus::Released, $challenge->fresh()->status);
    }
}
