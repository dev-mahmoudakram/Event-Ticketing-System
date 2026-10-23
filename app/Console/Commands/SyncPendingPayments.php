<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TicketPaymentStatus;
use App\Models\TicketPayment;
use App\Services\TicketPaymentConfirmer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The safety net under the Kashier webhook: asks Kashier about every payment link still open,
 * so a payment whose webhook never arrived still issues its ticket, and an expired link is
 * recorded as expired.
 */
class SyncPendingPayments extends Command
{
    protected $signature = 'payments:sync';

    protected $description = 'Check open Kashier payment links and issue the tickets that were paid';

    public function handle(TicketPaymentConfirmer $confirmer): int
    {
        $checked = 0;
        $failed = 0;

        TicketPayment::query()
            ->where('status', TicketPaymentStatus::Pending)
            ->whereNotNull('session_id')
            ->with('ticket')
            ->chunkById(100, function ($payments) use ($confirmer, &$checked, &$failed): void {
                foreach ($payments as $payment) {
                    try {
                        $confirmer->confirm($payment);
                        $checked++;
                    } catch (Throwable $exception) {
                        $failed++;
                        Log::warning('Could not sync a Kashier payment.', [
                            'ticket_payment_id' => $payment->id,
                            'exception' => $exception,
                        ]);
                    }
                }
            });

        $this->info("Checked {$checked} open payment links; {$failed} could not be checked.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
