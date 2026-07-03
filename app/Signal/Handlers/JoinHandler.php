<?php

namespace App\Signal\Handlers;

use App\Models\Game;
use App\Models\Participant;
use App\Models\Team;
use App\Services\Signal\SignalGateway;
use App\Signal\IncomingMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class JoinHandler
{
    private const KEYWORD = 'meedoen';

    public function __construct(
        protected SignalGateway $signal,
    ) {}

    public function applies(IncomingMessage $message): bool
    {
        return ! $message->isGroupMessage() && $message->textStartsWith(self::KEYWORD);
    }

    public function handle(IncomingMessage $message): void
    {
        $existing = Participant::query()->where('phone_number', $message->sourceNumber)->first();

        if ($existing !== null) {
            $this->ensureGroupMembership($existing);
            $this->signal->sendMessage([$message->sourceNumber], "Je doet al mee — je zit in Team {$existing->team?->name}.");

            return;
        }

        $name = trim(mb_substr(trim((string) $message->text), mb_strlen(self::KEYWORD)));

        if ($name === '') {
            $name = 'Speler '.mb_substr($message->sourceNumber, -4);
        }

        $team = $this->pickBalancedTeam();

        $participant = Participant::query()->create([
            'name' => $name,
            'phone_number' => $message->sourceNumber,
            'team_id' => $team->id,
            'joined_at' => now(),
        ]);
        $participant->setRelation('team', $team);

        $this->ensureGroupMembership($participant);

        $this->signal->sendMessage([$participant->phone_number], "Welkom bij Sauerland Games, {$participant->name}! Je zit in Team {$team->name}. Succes!");
    }

    /**
     * Add the participant to their team's and the main Signal group, if not already a member.
     *
     * Called both on first join and whenever an existing participant sends the join keyword
     * again, so a participant whose group invite failed earlier can self-heal by retrying.
     */
    private function ensureGroupMembership(Participant $participant): void
    {
        $participant->loadMissing('team');

        if ($participant->team?->signal_group_id !== null) {
            $this->tryAddGroupMember($participant->team->signal_group_id, $participant->phone_number);
        }

        $mainGroupId = Game::current()->signal_group_id;

        if ($mainGroupId !== null) {
            $this->tryAddGroupMember($mainGroupId, $participant->phone_number);
        }
    }

    private function tryAddGroupMember(string $groupId, string $phoneNumber): void
    {
        try {
            $this->signal->addGroupMembers($groupId, [$phoneNumber]);
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Kon deelnemer niet aan Signal-groep toevoegen.', [
                'group_id' => $groupId,
                'phone_number' => $phoneNumber,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function pickBalancedTeam(): Team
    {
        $teams = Team::query()->withCount('participants')->get();
        $minCount = $teams->min('participants_count');

        return $teams->where('participants_count', $minCount)->random();
    }
}
