<?php

namespace App\Models;

use App\Enums\ChallengeStatus;
use App\Enums\SubmissionStatus;
use Database\Factories\ChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'number', 'title', 'description', 'category', 'points',
    'is_secret', 'target_team_id', 'status', 'release_at', 'released_at', 'deadline_at',
])]
class Challenge extends Model
{
    /** @use HasFactory<ChallengeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function targetTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'target_team_id');
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * The teams this challenge is currently open for.
     *
     * @return Collection<int, Team>
     */
    public function eligibleTeams(): Collection
    {
        return $this->is_secret && $this->target_team_id
            ? Team::query()->whereKey($this->target_team_id)->get()
            : Team::all();
    }

    public function isApprovedForTeam(Team $team): bool
    {
        return $this->submissions()
            ->where('team_id', $team->id)
            ->where('status', SubmissionStatus::Approved)
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
            'status' => ChallengeStatus::class,
            'release_at' => 'datetime',
            'released_at' => 'datetime',
            'deadline_at' => 'datetime',
        ];
    }
}
