<?php

namespace App\Signal\Handlers;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Services\Game\ScoringService;
use App\Signal\IncomingMessage;

class ReactionHandler
{
    public function __construct(
        protected ScoringService $scoring,
    ) {}

    public function applies(IncomingMessage $message): bool
    {
        return $message->hasReaction()
            && $message->reaction->isThumbsUp()
            && $this->isOrganizer($message->sourceNumber);
    }

    public function handle(IncomingMessage $message): void
    {
        $reaction = $message->reaction;

        $submission = Submission::query()
            ->where('message_author', $reaction->targetAuthorNumber)
            ->where('message_timestamp', $reaction->targetSentTimestamp)
            ->first();

        if ($submission === null) {
            return;
        }

        if ($reaction->isRemove) {
            $this->scoring->revoke($submission);

            return;
        }

        if ($submission->status === SubmissionStatus::Approved) {
            return;
        }

        $this->scoring->approve($submission, $message->sourceNumber);
    }

    private function isOrganizer(string $phoneNumber): bool
    {
        return in_array($phoneNumber, config('services.signal.organizers'), true);
    }
}
