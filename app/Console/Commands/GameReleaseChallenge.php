<?php

namespace App\Console\Commands;

use App\Actions\Game\ReleaseChallenge;
use App\Models\Challenge;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game:release {number : Het opdrachtnummer}')]
#[Description('Geef een opdracht direct vrij aan de betrokken team(s)')]
class GameReleaseChallenge extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ReleaseChallenge $action): int
    {
        $challenge = Challenge::query()->where('number', $this->argument('number'))->first();

        if ($challenge === null) {
            $this->error('Opdracht niet gevonden.');

            return self::FAILURE;
        }

        $action->handle($challenge);
        $this->info("Opdracht #{$challenge->number} vrijgegeven.");

        return self::SUCCESS;
    }
}
