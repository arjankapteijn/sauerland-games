<?php

namespace App\Console\Commands;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\Game;
use App\Models\Team;
use App\Services\Signal\SignalGateway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game:expire-overdue')]
#[Description('Mark released challenges past their deadline as expired and announce which teams missed them')]
class GameExpireChallenges extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SignalGateway $signal): int
    {
        Challenge::query()
            ->where('status', ChallengeStatus::Released)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<=', now())
            ->each(function (Challenge $challenge) use ($signal): void {
                $missed = $challenge->eligibleTeams()->reject(fn (Team $team) => $challenge->isApprovedForTeam($team));

                $challenge->update(['status' => ChallengeStatus::Expired]);

                $mainGroupId = Game::current()->signal_group_id;

                if ($missed->isNotEmpty() && $mainGroupId !== null) {
                    $names = $missed->pluck('name')->implode(', ');
                    $signal->sendMessage([$mainGroupId], "⏰ De tijd is om voor #{$challenge->number} '{$challenge->title}'. Team(s) {$names} hebben 'm niet op tijd afgerond.");
                }

                $this->info("Opdracht #{$challenge->number} verlopen.");
            });

        return self::SUCCESS;
    }
}
