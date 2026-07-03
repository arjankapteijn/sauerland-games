<?php

namespace Tests\Feature\Game;

use App\Enums\ChallengeStatus;
use App\Enums\SubmissionStatus;
use App\Models\Challenge;
use App\Models\Submission;
use App\Models\Team;
use App\Services\Game\ScoringService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    public function test_standings_message_lists_open_challenges_and_omits_fully_approved_ones(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $rood = Team::factory()->create(['name' => 'Rood']);
        $blauw = Team::factory()->create(['name' => 'Blauw']);

        $open = Challenge::factory()->released()->create(['number' => 5]);
        $done = Challenge::factory()->released()->create(['number' => 9]);

        foreach ([$rood, $blauw] as $team) {
            Submission::factory()->approved()->for($done)->for($team)->create();
        }

        $message = $this->scoring()->standingsMessage();

        $this->assertStringContainsString('#5', $message);
        $this->assertStringNotContainsString('#9', $message);
    }

    public function test_first_approval_within_the_speed_bonus_window_awards_bonus_points(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->create([
            'points' => 10,
            'status' => ChallengeStatus::Released,
            'released_at' => now()->subMinutes(10),
        ]);
        $submission = Submission::factory()->for($challenge)->for($team)->create([
            'message_timestamp' => now()->subMinutes(5)->getTimestampMs(),
        ]);

        $event = $this->scoring()->approve($submission, '+3161999999');

        $this->assertSame(15, $event->points);
    }

    public function test_approval_outside_the_speed_bonus_window_awards_base_points_only(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->create([
            'points' => 10,
            'status' => ChallengeStatus::Released,
            'released_at' => now()->subHours(2),
        ]);
        $submission = Submission::factory()->for($challenge)->for($team)->create([
            'message_timestamp' => now()->subMinutes(30)->getTimestampMs(),
        ]);

        $event = $this->scoring()->approve($submission, '+3161999999');

        $this->assertSame(10, $event->points);
    }

    public function test_bonus_is_based_on_when_the_submission_was_sent_not_when_it_is_approved(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->create([
            'points' => 10,
            'status' => ChallengeStatus::Released,
            'released_at' => now()->subHours(3),
        ]);
        // Sent well within the window right after release, but the organizer only
        // gets round to approving it hours later (now).
        $submission = Submission::factory()->for($challenge)->for($team)->create([
            'message_timestamp' => now()->subHours(3)->addMinutes(5)->getTimestampMs(),
        ]);

        $event = $this->scoring()->approve($submission, '+3161999999');

        $this->assertSame(15, $event->points);
    }

    public function test_second_teams_approval_does_not_get_the_speed_bonus(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $rood = Team::factory()->create();
        $blauw = Team::factory()->create();
        $challenge = Challenge::factory()->create([
            'points' => 10,
            'status' => ChallengeStatus::Released,
            'released_at' => now()->subMinutes(5),
        ]);

        $first = Submission::factory()->for($challenge)->for($rood)->create([
            'message_timestamp' => now()->subMinutes(4)->getTimestampMs(),
        ]);
        $second = Submission::factory()->for($challenge)->for($blauw)->create([
            'message_timestamp' => now()->subMinutes(2)->getTimestampMs(),
        ]);

        $this->scoring()->approve($first, '+3161999999');
        $event = $this->scoring()->approve($second, '+3161999999');

        $this->assertSame(10, $event->points);
    }

    public function test_the_speed_bonus_goes_to_whoever_sent_first_even_if_reviewed_out_of_order(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $rood = Team::factory()->create();
        $blauw = Team::factory()->create();
        $challenge = Challenge::factory()->create([
            'points' => 10,
            'status' => ChallengeStatus::Released,
            'released_at' => now()->subMinutes(5),
        ]);

        $sentFirst = Submission::factory()->for($challenge)->for($rood)->create([
            'message_timestamp' => now()->subMinutes(4)->getTimestampMs(),
        ]);
        $sentSecond = Submission::factory()->for($challenge)->for($blauw)->create([
            'message_timestamp' => now()->subMinutes(2)->getTimestampMs(),
        ]);

        // Organizer happens to review the dashboard bottom-up and approves the
        // later submission first.
        $reviewedFirstEvent = $this->scoring()->approve($sentSecond, '+3161999999');
        $reviewedSecondEvent = $this->scoring()->approve($sentFirst, '+3161999999');

        $this->assertSame(10, $reviewedFirstEvent->points);
        $this->assertSame(15, $reviewedSecondEvent->points);
    }

    public function test_approving_an_already_approved_submission_is_idempotent(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->released()->create(['points' => 10]);
        $submission = Submission::factory()->for($challenge)->for($team)->create();

        $first = $this->scoring()->approve($submission, '+3161999999');
        $second = $this->scoring()->approve($submission->fresh(), '+3161999999');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $submission->fresh()->status === SubmissionStatus::Approved ? 1 : 0);
    }

    private function scoring(): ScoringService
    {
        return app(ScoringService::class);
    }
}
