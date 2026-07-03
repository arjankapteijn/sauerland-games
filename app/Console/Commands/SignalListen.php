<?php

namespace App\Console\Commands;

use App\Services\Signal\SignalGateway;
use App\Signal\SignalMessageProcessor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('signal:listen {--once : Process a single batch and exit, useful for testing}')]
#[Description('Continuously long-poll the Signal API and dispatch incoming messages')]
class SignalListen extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SignalGateway $signal, SignalMessageProcessor $processor): int
    {
        $this->info('Luisteren naar Signal-berichten... (ctrl+c om te stoppen)');

        do {
            try {
                foreach ($signal->receive(timeout: 10) as $raw) {
                    $processor->process($raw);
                }
            } catch (Throwable $e) {
                report($e);
                sleep(3);
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
