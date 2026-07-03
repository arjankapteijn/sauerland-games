<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\Signal\SignalGateway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game:setup')]
#[Description('Create the Signal groups for the main game and both teams')]
class GameSetup extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SignalGateway $signal): int
    {
        $organizers = config('services.signal.organizers');

        if ($organizers === []) {
            $this->error('Stel eerst SIGNAL_ORGANIZERS in .env in (telefoonnummers van Daniel en jou, comma-separated).');

            return self::FAILURE;
        }

        $mainGroupId = config('services.signal.main_group_id');

        if (! is_string($mainGroupId) || $mainGroupId === '') {
            $mainGroupId = $signal->createGroup('Sauerland Games', $organizers, 'Aankondigingen en tussenstanden.');
            $this->info("Hoofdgroep aangemaakt: {$mainGroupId}");
            $this->warn("Zet SIGNAL_MAIN_GROUP_ID={$mainGroupId} in je .env bestand.");
        } else {
            $this->info('Hoofdgroep bestaat al.');
        }

        foreach (Team::all() as $team) {
            if ($team->signal_group_id !== null) {
                $this->info("Team {$team->name} heeft al een groep.");

                continue;
            }

            $groupId = $signal->createGroup("Sauerland Games — Team {$team->name}", $organizers);
            $team->update(['signal_group_id' => $groupId]);
            $this->info("Team {$team->name} groep aangemaakt: {$groupId}");
        }

        return self::SUCCESS;
    }
}
