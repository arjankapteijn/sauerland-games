<?php

namespace App\Signal;

readonly class IncomingMessage
{
    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function __construct(
        public string $sourceNumber,
        public int $timestamp,
        public ?string $text,
        public ?string $groupId,
        public array $attachments,
        public ?IncomingReaction $reaction,
    ) {}

    /**
     * Parse a single raw item as returned by SignalGateway::receive(), i.e. the
     * `{"account": ..., "envelope": {...}}` shape. Returns null for envelopes
     * without a dataMessage (receipts, typing indicators, sync messages, calls).
     *
     * @param  array<string, mixed>  $raw
     */
    public static function fromRaw(array $raw): ?self
    {
        $envelope = $raw['envelope'] ?? null;
        $dataMessage = is_array($envelope) ? ($envelope['dataMessage'] ?? null) : null;

        if (! is_array($envelope) || ! is_array($dataMessage)) {
            return null;
        }

        $sourceNumber = $envelope['sourceNumber'] ?? $envelope['source'] ?? null;

        if (! is_string($sourceNumber) || $sourceNumber === '') {
            return null;
        }

        return new self(
            sourceNumber: $sourceNumber,
            timestamp: (int) ($dataMessage['timestamp'] ?? $envelope['timestamp'] ?? 0),
            text: is_string($dataMessage['message'] ?? null) ? $dataMessage['message'] : null,
            groupId: $dataMessage['groupInfo']['groupId'] ?? null,
            attachments: is_array($dataMessage['attachments'] ?? null) ? $dataMessage['attachments'] : [],
            reaction: IncomingReaction::fromDataMessage($dataMessage),
        );
    }

    public function isGroupMessage(): bool
    {
        return $this->groupId !== null;
    }

    public function hasAttachments(): bool
    {
        return $this->attachments !== [];
    }

    public function hasReaction(): bool
    {
        return $this->reaction !== null;
    }

    public function firstAttachmentId(): ?string
    {
        return $this->attachments[0]['id'] ?? null;
    }

    public function extractChallengeNumber(): ?int
    {
        if ($this->text !== null && preg_match('/#\s*(\d+)/', $this->text, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    public function textEquals(string $keyword): bool
    {
        return $this->text !== null && mb_strtolower(trim($this->text)) === mb_strtolower($keyword);
    }

    public function textStartsWith(string $keyword): bool
    {
        return $this->text !== null && str_starts_with(mb_strtolower(trim($this->text)), mb_strtolower($keyword));
    }
}
