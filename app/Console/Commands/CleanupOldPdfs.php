<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOldPdfs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pdfs:cleanup {--days=7 : Number of days after which to delete PDFs}';

    /**
     * The console command description.
     */
    protected $description = 'Verwijder PDF bestanden ouder dan X dagen (standaard 7 dagen)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);
        
        $this->info("Zoeken naar PDF's ouder dan {$days} dagen (voor {$cutoffDate->format('d-m-Y H:i')})...");

        // Zoek orders met PDF's ouder dan X dagen
        $orders = Order::whereNotNull('pdf_path')
            ->where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['shipped', 'delivered', 'cancelled', 'refunded'])
            ->get();

        $deleted = 0;
        $errors = 0;

        foreach ($orders as $order) {
            try {
                // Check of bestand bestaat
                if (Storage::disk('local')->exists($order->pdf_path)) {
                    // Verwijder het bestand
                    Storage::disk('local')->delete($order->pdf_path);
                    
                    // Update de order
                    $order->update([
                        'pdf_path' => null,
                        'pdf_stored_name' => null,
                    ]);
                    
                    $deleted++;
                    $this->line("  ✓ Verwijderd: {$order->order_number} ({$order->pdf_original_name})");
                    
                    Log::info("PDF cleanup: Deleted PDF for order {$order->order_number}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Fout bij {$order->order_number}: {$e->getMessage()}");
                Log::error("PDF cleanup error for order {$order->order_number}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Klaar! {$deleted} PDF('s) verwijderd, {$errors} fouten.");

        return self::SUCCESS;
    }
}
