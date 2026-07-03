<?php

namespace App\Signal\Handlers;

use App\Models\Participant;
use App\Models\Team;
use App\Services\Signal\SignalGateway;
use App\Signal\IncomingMessage;

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

        if ($team->signal_group_id !== null) {
            $this->signal->addGroupMembers($team->signal_group_id, [$participant->phone_number]);
        }

        $mainGroupId = config('services.signal.main_group_id');

        if (is_string($mainGroupId) && $mainGroupId !== '') {
            $this->signal->addGroupMembers($mainGroupId, [$participant->phone_number]);
        }

        $this->signal->sendMessage([$participant->phone_number], "Welkom bij Sauerland Games, {$participant->name}! Je zit in Team {$team->name}. Succes!");
    }

    private function pickBalancedTeam(): Team
    {
        $teams = Team::query()->withCount('participants')->get();
        $minCount = $teams->min('participants_count');

        return $teams->where('participants_count', $minCount)->random();
    }
}
