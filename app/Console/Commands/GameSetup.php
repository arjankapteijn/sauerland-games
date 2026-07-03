<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Team;
use App\Services\Signal\SignalGateway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;

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
            $this->error('Stel eerst SIGNAL_ORGANIZERS in .env in (telefoonnummer(s) van de jury, comma-separated).');

            return self::FAILURE;
        }

        $game = Game::current();
        $mainDescription = 'Aankondigingen en tussenstanden.';

        if ($game->signal_group_id === null) {
            // Bestaat de groep al op Signal (bijv. een vorige poging kwam wel
            // aan maar de request timede lokaal uit)? Dan hergebruiken i.p.v.
            // een dubbele hoofdgroep aan te maken.
            $existing = $this->findGroupByName($signal, 'Sauerland Games');

            $mainGroupId = $existing ?? $signal->createGroup('Sauerland Games', $organizers, $mainDescription);
            $game->update(['signal_group_id' => $mainGroupId]);
            $this->info(($existing !== null ? 'Hoofdgroep gevonden (al aangemaakt): ' : 'Hoofdgroep aangemaakt: ').$mainGroupId);
        } else {
            $this->info('Hoofdgroep bestaat al.');
        }

        $this->applyGroupAvatar($signal, $game->signal_group_id, 'icon.png', 'Sauerland Games', $mainDescription);

        foreach (Team::all() as $team) {
            $groupName = "Sauerland Games — Team {$team->name}";

            if ($team->signal_group_id === null) {
                $existing = $this->findGroupByName($signal, $groupName);
                $groupId = $existing ?? $signal->createGroup($groupName, $organizers);
                $team->update(['signal_group_id' => $groupId]);
                $this->info(($existing !== null ? "Team {$team->name} groep gevonden (al aangemaakt): " : "Team {$team->name} groep aangemaakt: ").$groupId);
            } else {
                $this->info("Team {$team->name} heeft al een groep.");
            }

            $this->applyGroupAvatar($signal, $team->signal_group_id, 'team-'.Str::slug($team->name).'.png', $groupName);
        }

        return self::SUCCESS;
    }

    private function findGroupByName(SignalGateway $signal, string $name): ?string
    {
        foreach ($signal->listGroups() as $group) {
            if (($group['name'] ?? null) === $name) {
                return $group['id'];
            }
        }

        return null;
    }

    /**
     * Set the group photo from a pre-rendered PNG under docs/, if one exists for it.
     */
    private function applyGroupAvatar(SignalGateway $signal, ?string $groupId, string $filename, string $groupName, string $description = ''): void
    {
        $path = base_path("docs/{$filename}");

        if ($groupId === null || ! is_file($path)) {
            return;
        }

        try {
            $signal->updateGroupAvatar($groupId, base64_encode((string) file_get_contents($path)), $groupName, $description);
        } catch (ConnectionException|RequestException $e) {
            $this->warn("Kon groepsfoto niet zetten voor groep {$groupId}: {$e->getMessage()}");
        }
    }
}
