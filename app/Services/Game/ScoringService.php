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
                $isFirstApproval = ! $challenge->submissions()->where('status', SubmissionStatus::Approved)->exists();
                $points = $challenge->points + $this->speedBonus($challenge, $isFirstApproval);

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

    private function speedBonus(Challenge $challenge, bool $isFirstApproval): int
    {
        if (! $isFirstApproval || $challenge->released_at === null) {
            return 0;
        }

        $deadline = $challenge->released_at->copy()->addMinutes(self::SPEED_BONUS_WINDOW_MINUTES);

        return Carbon::now()->lte($deadline) ? self::SPEED_BONUS_POINTS : 0;
    }

    private function announce(string $message): void
    {
        $mainGroupId = Game::current()->signal_group_id;

        if ($mainGroupId === null) {
            return;
        }

        $this->signal->sendMessage([$mainGroupId], $message);
    }
}
