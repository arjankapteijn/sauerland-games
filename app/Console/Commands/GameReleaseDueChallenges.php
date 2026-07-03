<?php

namespace App\Console\Commands;

use App\Actions\Game\ReleaseChallenge;
use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game:release-due')]
#[Description('Release all challenges whose scheduled release_at has passed')]
class GameReleaseDueChallenges extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ReleaseChallenge $action): int
    {
        Challenge::query()
            ->where('status', ChallengeStatus::Draft)
            ->whereNotNull('release_at')
            ->where('release_at', '<=', now())
            ->each(function (Challenge $challenge) use ($action): void {
                $action->handle($challenge);
                $this->info("Opdracht #{$challenge->number} automatisch vrijgegeven.");
            });

        return self::SUCCESS;
    }
}
