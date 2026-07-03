<?php

namespace App\Signal;

readonly class IncomingReaction
{
    private const THUMBS_UP = ['👍', '👍🏻', '👍🏼', '👍🏽', '👍🏾', '👍🏿'];

    public function __construct(
        public string $emoji,
        public bool $isRemove,
        public string $targetAuthorNumber,
        public int $targetSentTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $dataMessage
     */
    public static function fromDataMessage(array $dataMessage): ?self
    {
        $reaction = $dataMessage['reaction'] ?? null;

        if (! is_array($reaction)) {
            return null;
        }

        $targetAuthorNumber = $reaction['targetAuthorNumber'] ?? null;

        if (! is_string($targetAuthorNumber) || $targetAuthorNumber === '') {
            return null;
        }

        return new self(
            emoji: (string) ($reaction['emoji'] ?? ''),
            isRemove: (bool) ($reaction['isRemove'] ?? false),
            targetAuthorNumber: $targetAuthorNumber,
            targetSentTimestamp: (int) ($reaction['targetSentTimestamp'] ?? 0),
        );
    }

    public function isThumbsUp(): bool
    {
        return in_array($this->emoji, self::THUMBS_UP, true);
    }
}
