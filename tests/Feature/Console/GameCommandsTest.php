<?php

namespace Tests\Feature\Console;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GameCommandsTest extends TestCase
{
    public function test_game_release_releases_a_specific_challenge(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        Team::factory()->create(['signal_group_id' => 'group-1']);
        $challenge = Challenge::factory()->create(['number' => 4]);

        $this->artisan('game:release', ['number' => 4])->assertSuccessful();

        $this->assertSame(ChallengeStatus::Released, $challenge->fresh()->status);
        Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/v2/send'));
    }

    public function test_game_release_due_only_releases_challenges_whose_time_has_come(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        Team::factory()->create();
        $due = Challenge::factory()->create(['number' => 1, 'release_at' => now()->subMinute()]);
        $future = Challenge::factory()->create(['number' => 2, 'release_at' => now()->addHour()]);

        $this->artisan('game:release-due')->assertSuccessful();

        $this->assertSame(ChallengeStatus::Released, $due->fresh()->status);
        $this->assertSame(ChallengeStatus::Draft, $future->fresh()->status);
    }

    public function test_game_expire_overdue_marks_challenges_expired_and_announces_missing_teams(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);
        config(['services.signal.main_group_id' => 'main-group']);

        $team = Team::factory()->create(['name' => 'Rood']);
        $challenge = Challenge::factory()->create([
            'status' => ChallengeStatus::Released,
            'deadline_at' => now()->subMinute(),
        ]);

        $this->artisan('game:expire-overdue')->assertSuccessful();

        $this->assertSame(ChallengeStatus::Expired, $challenge->fresh()->status);
        Http::assertSent(fn ($request) => str_contains((string) ($request->data()['message'] ?? ''), 'Rood'));
    }

    public function test_game_setup_creates_the_main_and_team_groups(): void
    {
        config(['services.signal.organizers' => ['+3161999999']]);
        Http::fake([
            '*/v1/groups/*' => Http::sequence()
                ->push([], 200) // GET: hoofdgroep nog niet gevonden
                ->push(['id' => 'main-group-id'], 201) // POST: hoofdgroep aanmaken
                ->push([], 200) // GET: team Rood-groep nog niet gevonden
                ->push(['id' => 'rood-group-id'], 201) // POST: team Rood-groep aanmaken
                ->push([], 200) // GET: team Blauw-groep nog niet gevonden
                ->push(['id' => 'blauw-group-id'], 201), // POST: team Blauw-groep aanmaken
        ]);

        Team::factory()->create(['name' => 'Rood']);
        Team::factory()->create(['name' => 'Blauw']);

        $this->artisan('game:setup')->assertSuccessful();

        $this->assertSame(2, Team::query()->whereNotNull('signal_group_id')->count());
    }

    public function test_game_setup_reuses_an_existing_signal_group_instead_of_duplicating(): void
    {
        config(['services.signal.organizers' => ['+3161999999']]);
        Http::fake([
            '*/v1/groups/*' => Http::response([
                ['id' => 'existing-main-id', 'name' => 'Sauerland Games'],
            ], 200),
        ]);

        Team::factory()->create(['name' => 'Rood', 'signal_group_id' => 'already-set']);

        $this->artisan('game:setup')->assertSuccessful();

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_game_expire_overdue_does_not_announce_when_all_teams_completed_it(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);
        config(['services.signal.main_group_id' => 'main-group']);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->create([
            'status' => ChallengeStatus::Released,
            'deadline_at' => now()->subMinute(),
        ]);
        Submission::factory()->approved()->for($challenge)->for($team)->create();

        $this->artisan('game:expire-overdue')->assertSuccessful();

        $this->assertSame(ChallengeStatus::Expired, $challenge->fresh()->status);
        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), '/v2/send'));
    }
}
