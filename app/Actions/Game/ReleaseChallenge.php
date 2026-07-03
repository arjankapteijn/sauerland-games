<?php

namespace App\Actions\Game;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Services\Signal\SignalGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class ReleaseChallenge
{
    public function __construct(
        protected SignalGateway $signal,
    ) {}

    /**
     * Release the challenge and notify eligible teams over Signal.
     *
     * @return array<int, string> Names of teams that could not be notified.
     */
    public function handle(Challenge $challenge): array
    {
        if ($challenge->status === ChallengeStatus::Released) {
            return [];
        }

        $message = "📋 Nieuwe opdracht #{$challenge->number}: {$challenge->title}\n\n{$challenge->description}\n\n({$challenge->points} punten)";

        $failedTeams = [];

        foreach ($challenge->eligibleTeams() as $team) {
            if ($team->signal_group_id === null) {
                continue;
            }

            try {
                $this->signal->sendMessage([$team->signal_group_id], $message);
            } catch (ConnectionException|RequestException $e) {
                Log::warning('Kon Signal-bericht voor vrijgegeven opdracht niet versturen.', [
                    'challenge_id' => $challenge->id,
                    'team_id' => $team->id,
                    'exception' => $e->getMessage(),
                ]);

                $failedTeams[] = $team->name;
            }
        }

        $challenge->update([
            'status' => ChallengeStatus::Released,
            'released_at' => now(),
        ]);

        return $failedTeams;
    }
}
