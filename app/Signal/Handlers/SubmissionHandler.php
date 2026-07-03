<?php

namespace App\Signal\Handlers;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\Team;
use App\Services\Signal\SignalGateway;
use App\Signal\IncomingMessage;

class SubmissionHandler
{
    public function __construct(
        protected SignalGateway $signal,
    ) {}

    public function applies(IncomingMessage $message): bool
    {
        return $message->isGroupMessage() && $message->hasAttachments();
    }

    public function handle(IncomingMessage $message): void
    {
        $team = Team::query()->where('signal_group_id', $message->groupId)->first();

        if ($team === null) {
            return;
        }

        $number = $message->extractChallengeNumber();

        if ($number === null) {
            $this->signal->sendMessage([$message->groupId], 'Welke opdracht is dit? Zet #nummer in je bijschrift.');

            return;
        }

        $challenge = Challenge::query()->where('number', $number)->first();

        if ($challenge === null) {
            $this->signal->sendMessage([$message->groupId], "Opdracht #{$number} bestaat niet.");

            return;
        }

        if ($challenge->status !== ChallengeStatus::Released) {
            $this->signal->sendMessage([$message->groupId], "Opdracht #{$number} is nog niet vrijgegeven.");

            return;
        }

        if ($challenge->is_secret && $challenge->target_team_id !== $team->id) {
            $this->signal->sendMessage([$message->groupId], "Opdracht #{$number} is niet voor jullie team.");

            return;
        }

        if ($challenge->isApprovedForTeam($team)) {
            $this->signal->sendMessage([$message->groupId], "Opdracht #{$number} is al goedgekeurd voor jullie team!");

            return;
        }

        $participant = Participant::query()
            ->where('phone_number', $message->sourceNumber)
            ->where('team_id', $team->id)
            ->first();

        Submission::query()->firstOrCreate(
            [
                'message_author' => $message->sourceNumber,
                'message_timestamp' => $message->timestamp,
            ],
            [
                'challenge_id' => $challenge->id,
                'team_id' => $team->id,
                'participant_id' => $participant?->id,
                'attachment_id' => $message->firstAttachmentId(),
                'caption' => $message->text,
            ],
        );
    }
}
