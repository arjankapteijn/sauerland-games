<?php

namespace App\Signal\Handlers;

use App\Services\Game\ScoringService;
use App\Services\Signal\SignalGateway;
use App\Signal\IncomingMessage;

class CommandHandler
{
    private const KEYWORD = 'stand';

    public function __construct(
        protected SignalGateway $signal,
        protected ScoringService $scoring,
    ) {}

    public function applies(IncomingMessage $message): bool
    {
        return $message->textEquals(self::KEYWORD);
    }

    public function handle(IncomingMessage $message): void
    {
        $recipient = $message->groupId ?? $message->sourceNumber;

        $this->signal->sendMessage([$recipient], $this->scoring->standingsMessage());
    }
}
