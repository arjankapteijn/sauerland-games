<?php

namespace App\Actions\Game;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Services\Signal\SignalGateway;

class ReleaseChallenge
{
    public function __construct(
        protected SignalGateway $signal,
    ) {}

    public function handle(Challenge $challenge): void
    {
        if ($challenge->status === ChallengeStatus::Released) {
            return;
        }

        $message = "📋 Nieuwe opdracht #{$challenge->number}: {$challenge->title}\n\n{$challenge->description}\n\n({$challenge->points} punten)";

        foreach ($challenge->eligibleTeams() as $team) {
            if ($team->signal_group_id !== null) {
                $this->signal->sendMessage([$team->signal_group_id], $message);
            }
        }

        $challenge->update([
            'status' => ChallengeStatus::Released,
            'released_at' => now(),
        ]);
    }
}
