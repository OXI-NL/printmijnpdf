<?php

namespace App\Console\Commands;

use App\Mail\PaymentReminder;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:send-payment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stuur betalingsherinneringen voor orders die 2 uur pending zijn';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Zoek orders die:
        // - Status 'pending' hebben
        // - Meer dan 2 uur geleden aangemaakt zijn
        // - Nog geen herinnering hebben gehad (reminder_sent_at is null)
        $orders = Order::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(2))
            ->whereNull('reminder_sent_at')
            ->get();

        $count = 0;

        foreach ($orders as $order) {
            try {
                Mail::to($order->customer_email, $order->customer_name)
                    ->send(new PaymentReminder($order));
                
                // Markeer dat herinnering is verstuurd
                $order->update(['reminder_sent_at' => now()]);
                
                $count++;
                Log::info("Payment reminder sent for order {$order->order_number}");
                
            } catch (\Exception $e) {
                Log::error("Failed to send payment reminder for order {$order->order_number}: " . $e->getMessage());
            }
        }

        $this->info("Verzonden: {$count} betalingsherinneringen");
        
        return Command::SUCCESS;
    }
}
