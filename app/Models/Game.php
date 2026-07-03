<?php

namespace App\Models;

use App\Services\Signal\SignalGateway;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['signal_group_id', 'thanked_at'])]
class Game extends Model
{
    /**
     * The game is a singleton: there is only ever one row.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    /**
     * Send a message to the main Signal group, if one is configured.
     *
     * @return bool Whether a main group was configured and the message was sent.
     */
    public function announceToMainGroup(SignalGateway $signal, string $message): bool
    {
        if ($this->signal_group_id === null) {
            return false;
        }

        $signal->sendMessage([$this->signal_group_id], $message);

        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thanked_at' => 'datetime',
        ];
    }
}
