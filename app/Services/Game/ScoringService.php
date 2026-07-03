<?php

namespace App\Services\Game;

use App\Enums\ChallengeStatus;
use App\Enums\SubmissionStatus;
use App\Models\Challenge;
use App\Models\Game;
use App\Models\ScoreEvent;
use App\Models\Submission;
use App\Models\Team;
use App\Services\Signal\SignalGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    private const SPEED_BONUS_POINTS = 5;

    private const SPEED_BONUS_WINDOW_MINUTES = 30;

    public function __construct(
        protected SignalGateway $signal,
    ) {}

    public function approve(Submission $submission, string $organizerNumber): ScoreEvent
    {
        $submission->loadMissing('challenge', 'team', 'scoreEvent');

        if ($submission->status === SubmissionStatus::Approved) {
            return $submission->scoreEvent ?? ScoreEvent::query()->create([
                'team_id' => $submission->team_id,
                'challenge_id' => $submission->challenge_id,
                'submission_id' => $submission->id,
                'points' => $submission->challenge->points,
                'reason' => "Opdracht #{$submission->challenge->number} '{$submission->challenge->title}' goedgekeurd",
            ]);
        }

        $challenge = $submission->challenge;

        $event = Cache::lock("challenge:{$challenge->id}:approve", 10)->block(5, function () use ($submission, $challenge, $organizerNumber) {
            return DB::transaction(function () use ($submission, $challenge, $organizerNumber) {
                $isFirstApproval = $this->isFirstApproval($challenge, $submission);
                $points = $challenge->points + $this->speedBonus($challenge, $submission, $isFirstApproval);

                $event = ScoreEvent::query()->create([
                    'team_id' => $submission->team_id,
                    'challenge_id' => $challenge->id,
                    'submission_id' => $submission->id,
                    'points' => $points,
                    'reason' => "Opdracht #{$challenge->number} '{$challenge->title}' goedgekeurd",
                ]);

                $submission->update([
                    'status' => SubmissionStatus::Approved,
                    'approved_by' => $organizerNumber,
                    'approved_at' => now(),
                ]);

                return $event;
            });
        });

        $this->announce("🎉 Team {$submission->team->name} heeft #{$challenge->number} '{$challenge->title}' voltooid! +{$event->points} punten.\n\n".$this->standingsMessage());

        return $event;
    }

    public function revoke(Submission $submission): void
    {
        $submission->loadMissing('challenge', 'team', 'scoreEvent');

        if ($submission->status !== SubmissionStatus::Approved) {
            return;
        }

        DB::transaction(function () use ($submission) {
            $submission->scoreEvent?->delete();

            $submission->update([
                'status' => SubmissionStatus::Pending,
                'approved_by' => null,
                'approved_at' => null,
            ]);
        });

        $this->announce("⚠️ De goedkeuring voor #{$submission->challenge->number} '{$submission->challenge->title}' bij Team {$submission->team->name} is ingetrokken.\n\n".$this->standingsMessage());
    }

    public function standingsMessage(): string
    {
        $standings = Team::query()
            ->withSum('scoreEvents as score', 'points')
            ->orderByDesc('score')
            ->get()
            ->map(fn (Team $team) => "{$team->name} ".((int) $team->score))
            ->implode(' – ');

        $open = Challenge::query()
            ->where('status', ChallengeStatus::Released)
            ->get()
            ->reject(fn (Challenge $challenge) => $challenge->eligibleTeams()->every(fn (Team $team) => $challenge->isApprovedForTeam($team)))
            ->map(fn (Challenge $challenge) => "#{$challenge->number}")
            ->implode(', ');

        return "Stand: {$standings}.".($open !== '' ? " Nog open: {$open}." : ' Alle opdrachten zijn afgerond!');
    }

    /**
     * A submission counts as "first" when no other still-viable submission (i.e. not
     * rejected) for this challenge was sent earlier — based on when the team's Signal
     * message came in, not on the order organizers happen to review submissions in.
     */
    private function isFirstApproval(Challenge $challenge, Submission $submission): bool
    {
        return ! $challenge->submissions()
            ->whereKeyNot($submission->id)
            ->where('status', '!=', SubmissionStatus::Rejected)
            ->where('message_timestamp', '<', $submission->message_timestamp)
            ->exists();
    }

    private function speedBonus(Challenge $challenge, Submission $submission, bool $isFirstApproval): int
    {
        if (! $isFirstApproval || $challenge->released_at === null) {
            return 0;
        }

        $deadline = $challenge->released_at->copy()->addMinutes(self::SPEED_BONUS_WINDOW_MINUTES);
        $submittedAt = Carbon::createFromTimestampMs($submission->message_timestamp);

        return $submittedAt->lte($deadline) ? self::SPEED_BONUS_POINTS : 0;
    }

    private function announce(string $message): void
    {
        Game::current()->announceToMainGroup($this->signal, $message);
    }
}
