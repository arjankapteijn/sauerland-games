<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'color', 'signal_group_id'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * @return HasMany<ScoreEvent, $this>
     */
    public function scoreEvents(): HasMany
    {
        return $this->hasMany(ScoreEvent::class);
    }

    /**
     * Secret challenges targeted only at this team.
     *
     * @return HasMany<Challenge, $this>
     */
    public function targetedChallenges(): HasMany
    {
        return $this->hasMany(Challenge::class, 'target_team_id');
    }

    public function score(): int
    {
        return (int) $this->scoreEvents()->sum('points');
    }
}
