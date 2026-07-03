<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\Signal\SignalGateway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game:send-thank-you')]
#[Description('Stuur eenmalig een bedankbericht naar de hoofdgroep zodra het weekend voorbij is')]
class GameSendThankYou extends Command
{
    private const string CLOSES_AT = '2026-10-05 12:00';

    /**
     * Execute the console command.
     */
    public function handle(SignalGateway $signal): int
    {
        $game = Game::current();

        if ($game->thanked_at !== null) {
            return self::SUCCESS;
        }

        if (now()->lt(self::CLOSES_AT)) {
            return self::SUCCESS;
        }

        if ($game->signal_group_id !== null) {
            $signal->sendMessage([$game->signal_group_id], '🏁 Het weekend zit erop — bedankt voor het meedoen allemaal, tot de volgende keer!');
        }

        $game->update(['thanked_at' => now()]);
        $this->info('Bedankbericht verstuurd.');

        return self::SUCCESS;
    }
}
