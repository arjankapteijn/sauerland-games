<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\Signal\SignalGateway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

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

        $game->update(['thanked_at' => now()]);

        try {
            if ($game->announceToMainGroup($signal, '🏁 Het weekend zit erop — bedankt voor het meedoen allemaal, tot de volgende keer!')) {
                $this->info('Bedankbericht verstuurd.');
            }
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Kon bedankbericht niet versturen.', [
                'game_id' => $game->id,
                'exception' => $e->getMessage(),
            ]);
        }

        return self::SUCCESS;
    }
}
