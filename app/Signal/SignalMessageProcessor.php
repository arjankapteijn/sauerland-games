<?php

namespace App\Signal;

use App\Signal\Handlers\CommandHandler;
use App\Signal\Handlers\JoinHandler;
use App\Signal\Handlers\ReactionHandler;
use App\Signal\Handlers\SubmissionHandler;

class SignalMessageProcessor
{
    public function __construct(
        protected ReactionHandler $reactionHandler,
        protected JoinHandler $joinHandler,
        protected CommandHandler $commandHandler,
        protected SubmissionHandler $submissionHandler,
    ) {}

    /**
     * @param  array<string, mixed>  $raw  A single item as returned by SignalGateway::receive().
     */
    public function process(array $raw): void
    {
        $message = IncomingMessage::fromRaw($raw);

        if ($message === null) {
            return;
        }

        foreach ([$this->reactionHandler, $this->joinHandler, $this->commandHandler, $this->submissionHandler] as $handler) {
            if ($handler->applies($message)) {
                $handler->handle($message);

                return;
            }
        }
    }
}
