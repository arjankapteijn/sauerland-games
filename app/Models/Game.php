<?php

namespace App\Models;

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thanked_at' => 'datetime',
        ];
    }
}
