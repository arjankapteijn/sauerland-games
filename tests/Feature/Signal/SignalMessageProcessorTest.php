<?php

namespace Tests\Feature\Signal;

use App\Enums\SubmissionStatus;
use App\Models\Challenge;
use App\Models\Game;
use App\Models\Participant;
use App\Models\ScoreEvent;
use App\Models\Submission;
use App\Models\Team;
use App\Signal\SignalMessageProcessor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SignalMessageProcessorTest extends TestCase
{
    public function test_join_message_creates_a_participant_and_adds_them_to_their_team_group(): void
    {
        Http::fake([
            '*/v1/groups/*/members' => Http::response('', 204),
            '*/v2/send' => Http::response(['timestamp' => '1'], 201),
        ]);

        $team = Team::factory()->create(['signal_group_id' => 'team-group-1']);

        $this->processor()->process($this->directMessage('+3161000001', 'MEEDOEN Jan'));

        $participant = Participant::query()->where('phone_number', '+3161000001')->firstOrFail();
        $this->assertSame('Jan', $participant->name);
        $this->assertNotNull($participant->team_id);

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/members')
            && in_array('+3161000001', $request->data()['members'] ?? [], true));

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/v2/send')
            && $request->data()['recipients'] === ['+3161000001']);
    }

    public function test_joining_twice_does_not_create_a_second_participant(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        Team::factory()->count(2)->create();
        $existing = Participant::factory()->for(Team::factory())->create(['phone_number' => '+3161000002']);

        $this->processor()->process($this->directMessage('+3161000002', 'meedoen'));

        $this->assertSame(1, Participant::query()->where('phone_number', '+3161000002')->count());
        $this->assertSame($existing->id, Participant::query()->where('phone_number', '+3161000002')->firstOrFail()->id);
    }

    public function test_submission_with_challenge_number_is_stored_against_the_teams_group(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create(['signal_group_id' => 'team-group-1']);
        $challenge = Challenge::factory()->released()->create(['number' => 7]);

        $this->processor()->process($this->groupMessage(
            source: '+3161000003',
            groupId: 'team-group-1',
            text: 'Klaar! #7',
            attachments: [['id' => 'attachment-1', 'contentType' => 'image/jpeg']],
            timestamp: 1_700_000_000_001,
        ));

        $submission = Submission::query()->where('message_timestamp', 1_700_000_000_001)->firstOrFail();
        $this->assertSame($challenge->id, $submission->challenge_id);
        $this->assertSame($team->id, $submission->team_id);
        $this->assertSame('attachment-1', $submission->attachment_id);
        $this->assertSame(SubmissionStatus::Pending, $submission->status);
    }

    public function test_submission_without_a_challenge_number_asks_for_one(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        Team::factory()->create(['signal_group_id' => 'team-group-1']);

        $this->processor()->process($this->groupMessage(
            source: '+3161000004',
            groupId: 'team-group-1',
            text: 'Klaar!',
            attachments: [['id' => 'attachment-1']],
        ));

        $this->assertSame(0, Submission::query()->count());
        Http::assertSent(fn ($request) => str_contains((string) $request->data()['message'] ?? '', '#nummer'));
    }

    public function test_organizer_thumbs_up_approves_the_matching_submission_and_awards_points(): void
    {
        config(['services.signal.organizers' => ['+3161999999']]);
        Game::query()->create(['signal_group_id' => 'main-group']);
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->released()->create(['points' => 10]);
        $submission = Submission::factory()->for($challenge)->for($team)->create([
            'message_author' => '+3161000005',
            'message_timestamp' => 1_700_000_000_002,
        ]);

        $this->processor()->process($this->reactionMessage(
            organizer: '+3161999999',
            targetAuthor: '+3161000005',
            targetTimestamp: 1_700_000_000_002,
        ));

        $submission->refresh();
        $this->assertSame(SubmissionStatus::Approved, $submission->status);
        $this->assertSame('+3161999999', $submission->approved_by);
        // 10 base points + 5 speed bonus (first approval, well within the window).
        $this->assertSame(15, ScoreEvent::query()->where('submission_id', $submission->id)->value('points'));

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/v2/send')
            && str_contains((string) ($request->data()['message'] ?? ''), 'voltooid'));
    }

    public function test_non_organizer_thumbs_up_is_ignored(): void
    {
        config(['services.signal.organizers' => ['+3161999999']]);
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->released()->create();
        $submission = Submission::factory()->for($challenge)->for($team)->create([
            'message_author' => '+3161000006',
            'message_timestamp' => 1_700_000_000_003,
        ]);

        $this->processor()->process($this->reactionMessage(
            organizer: '+3161000099',
            targetAuthor: '+3161000006',
            targetTimestamp: 1_700_000_000_003,
        ));

        $this->assertSame(SubmissionStatus::Pending, $submission->fresh()->status);
    }

    public function test_removed_reaction_revokes_a_previous_approval(): void
    {
        config(['services.signal.organizers' => ['+3161999999']]);
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        $team = Team::factory()->create();
        $challenge = Challenge::factory()->released()->create();
        $submission = Submission::factory()->approved()->for($challenge)->for($team)->create([
            'message_author' => '+3161000007',
            'message_timestamp' => 1_700_000_000_004,
        ]);
        ScoreEvent::factory()->create(['submission_id' => $submission->id, 'team_id' => $team->id, 'challenge_id' => $challenge->id]);

        $this->processor()->process($this->reactionMessage(
            organizer: '+3161999999',
            targetAuthor: '+3161000007',
            targetTimestamp: 1_700_000_000_004,
            isRemove: true,
        ));

        $submission->refresh();
        $this->assertSame(SubmissionStatus::Pending, $submission->status);
        $this->assertSame(0, ScoreEvent::query()->where('submission_id', $submission->id)->count());
    }

    public function test_stand_command_replies_with_the_standings_message(): void
    {
        Http::fake(['*' => Http::response(['timestamp' => '1'], 201)]);

        Team::factory()->create(['name' => 'Rood']);
        Team::factory()->create(['name' => 'Blauw']);

        $this->processor()->process($this->directMessage('+3161000008', 'stand'));

        Http::assertSent(fn ($request) => str_contains((string) ($request->data()['message'] ?? ''), 'Stand:'));
    }

    private function processor(): SignalMessageProcessor
    {
        return app(SignalMessageProcessor::class);
    }

    /**
     * @param  array<int, array<string, mixed>>  $attachments
     * @return array<string, mixed>
     */
    private function groupMessage(string $source, string $groupId, ?string $text, array $attachments = [], int $timestamp = 1_700_000_000_000): array
    {
        return [
            'envelope' => [
                'sourceNumber' => $source,
                'timestamp' => $timestamp,
                'dataMessage' => [
                    'timestamp' => $timestamp,
                    'message' => $text,
                    'groupInfo' => ['groupId' => $groupId],
                    'attachments' => $attachments,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function directMessage(string $source, ?string $text, int $timestamp = 1_700_000_000_000): array
    {
        return [
            'envelope' => [
                'sourceNumber' => $source,
                'timestamp' => $timestamp,
                'dataMessage' => [
                    'timestamp' => $timestamp,
                    'message' => $text,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reactionMessage(string $organizer, string $targetAuthor, int $targetTimestamp, bool $isRemove = false): array
    {
        return [
            'envelope' => [
                'sourceNumber' => $organizer,
                'timestamp' => $targetTimestamp + 1000,
                'dataMessage' => [
                    'timestamp' => $targetTimestamp + 1000,
                    'reaction' => [
                        'emoji' => '👍',
                        'isRemove' => $isRemove,
                        'targetAuthorNumber' => $targetAuthor,
                        'targetSentTimestamp' => $targetTimestamp,
                    ],
                ],
            ],
        ];
    }
}
