<div wire:poll.10s class="min-h-screen bg-slate-950 text-slate-100 px-6 py-8">
    <header class="mx-auto max-w-6xl flex items-center justify-between pb-8">
        <div>
            <p class="text-sm uppercase tracking-widest text-emerald-500 font-semibold">Sauerland Games</p>
            <h1 class="text-3xl font-extrabold tracking-tight">Live scorebord</h1>
        </div>
        <p class="text-sm text-slate-500 font-mono">ververst elke 10s</p>
    </header>

    <section class="mx-auto max-w-6xl grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10">
        @foreach ($teams as $team)
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 flex items-center justify-between">
                <span class="text-xl font-bold">{{ $team->name }}</span>
                <span class="text-5xl font-black tabular-nums" style="color: {{ $team->color }}">{{ (int) $team->score }}</span>
            </div>
        @endforeach
    </section>

    @if ($releaseWarning)
        <div class="mx-auto max-w-6xl mb-6 rounded-lg border border-amber-800 bg-amber-950 text-amber-300 px-4 py-3 text-sm">
            {{ $releaseWarning }}
        </div>
    @endif

    <section class="mx-auto max-w-6xl mb-10">
        <h2 class="text-lg font-bold mb-4">Wachtend op goedkeuring ({{ $pending->count() }})</h2>

        @if ($pending->isEmpty())
            <p class="text-slate-500">Geen openstaande inzendingen.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($pending as $submission)
                    <div wire:key="submission-{{ $submission->id }}" class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden flex flex-col">
                        @if ($submission->attachment_id)
                            <img src="{{ $signalApiUrl }}/v1/attachments/{{ $submission->attachment_id }}" alt="Inzending #{{ $submission->challenge->number }}" class="h-48 w-full object-cover bg-slate-800">
                        @endif
                        <div class="p-4 flex flex-col gap-2 flex-1">
                            <p class="font-semibold">#{{ $submission->challenge->number }} · {{ $submission->challenge->title }}</p>
                            <p class="text-sm text-slate-400">{{ $submission->team->name }} — {{ $submission->participant?->name ?? $submission->message_author }}</p>
                            <div class="mt-auto flex gap-2 pt-2">
                                <button wire:click="approve({{ $submission->id }})" class="flex-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 py-2 font-semibold">👍 Goedkeuren</button>
                                <button wire:click="reject({{ $submission->id }})" wire:confirm="Deze inzending afwijzen?" class="rounded-lg bg-slate-800 hover:bg-slate-700 px-3">✕</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mx-auto max-w-6xl">
        <h2 class="text-lg font-bold mb-4">Opdrachten</h2>
        <div class="rounded-xl border border-slate-800 bg-slate-900 divide-y divide-slate-800">
            @foreach ($challenges as $challenge)
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <div class="min-w-0">
                        <p class="font-medium truncate">#{{ $challenge->number }} · {{ $challenge->title }}</p>
                        <p class="text-xs text-slate-500">{{ $challenge->category }} · {{ $challenge->points }} punten</p>
                    </div>
                    @if ($challenge->status === $challengeStatuses::Draft)
                        <button wire:click="release({{ $challenge->id }})" class="shrink-0 rounded-lg bg-emerald-600 hover:bg-emerald-500 px-3 py-1.5 text-sm font-semibold">Nu vrijgeven</button>
                    @elseif ($challenge->status === $challengeStatuses::Released)
                        <span class="shrink-0 rounded-full bg-emerald-950 text-emerald-400 px-3 py-1 text-xs font-semibold">Live</span>
                    @else
                        <span class="shrink-0 rounded-full bg-slate-800 text-slate-500 px-3 py-1 text-xs font-semibold">Verlopen</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</div>
