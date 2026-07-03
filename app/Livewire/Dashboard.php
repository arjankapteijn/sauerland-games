<?php

namespace App\Livewire;

use App\Actions\Game\ReleaseChallenge;
use App\Enums\ChallengeStatus;
use App\Enums\SubmissionStatus;
use App\Models\Challenge;
use App\Models\Submission;
use App\Models\Team;
use App\Services\Game\ScoringService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Sauerland Games — Dashboard')]
class Dashboard extends Component
{
    public function approve(int $submissionId, ScoringService $scoring): void
    {
        $scoring->approve(Submission::findOrFail($submissionId), 'dashboard');
    }

    public function reject(int $submissionId): void
    {
        Submission::whereKey($submissionId)->update(['status' => SubmissionStatus::Rejected]);
    }

    public function release(int $challengeId, ReleaseChallenge $action): void
    {
        $action->handle(Challenge::findOrFail($challengeId));
    }

    public function render(): View
    {
        return view('livewire.dashboard', [
            'teams' => Team::query()->withSum('scoreEvents as score', 'points')->orderByDesc('score')->get(),
            'pending' => Submission::query()
                ->with(['challenge', 'team', 'participant'])
                ->where('status', SubmissionStatus::Pending)
                ->orderByDesc('message_timestamp')
                ->get(),
            'challenges' => Challenge::query()->orderBy('number')->get(),
            'signalApiUrl' => rtrim((string) config('services.signal.api_url'), '/'),
            'challengeStatuses' => ChallengeStatus::class,
        ]);
    }
}
