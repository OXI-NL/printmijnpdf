<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BookletImpositionService
{
    /**
     * Pad naar het Python script
     */
    protected string $scriptPath;
    
    /**
     * Python binary
     */
    protected string $pythonBin;

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/impose_booklet.py');
        $this->pythonBin = config('services.python.binary', 'python3');
    }

    /**
     * Maak impositie voor een order
     * 
     * @param Order $order
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public function createImposition(Order $order): array
    {
        // Alleen voor boekjes
        if ($order->binding_type !== 'booklet') {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Order is geen boekje'
            ];
        }

        // Check of input PDF bestaat
        $inputPath = Storage::disk('local')->path($order->pdf_path);
        if (!file_exists($inputPath)) {
            Log::error("Imposition: Input PDF niet gevonden voor order {$order->order_number}");
            return [
                'success' => false,
                'path' => null,
                'message' => 'Input PDF niet gevonden'
            ];
        }

        // Output pad
        $outputDir = Storage::disk('local')->path('pdfs/imposed');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $outputFilename = pathinfo($order->pdf_stored_name, PATHINFO_FILENAME) . '_imposed.pdf';
        $outputPath = $outputDir . '/' . $outputFilename;

        // Bepaal formaat
        $format = $order->format; // A4 of A5

        // Voer Python script uit
        $command = sprintf(
            '%s %s %s %s --format %s --quiet 2>&1',
            escapeshellcmd($this->pythonBin),
            escapeshellarg($this->scriptPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath),
            escapeshellarg($format)
        );

        Log::info("Imposition command: {$command}");

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $errorMsg = implode("\n", $output);
            Log::error("Imposition failed for order {$order->order_number}: {$errorMsg}");
            return [
                'success' => false,
                'path' => null,
                'message' => "Script fout: {$errorMsg}"
            ];
        }

        // Check of output bestaat
        if (!file_exists($outputPath)) {
            Log::error("Imposition: Output PDF niet aangemaakt voor order {$order->order_number}");
            return [
                'success' => false,
                'path' => null,
                'message' => 'Output PDF niet aangemaakt'
            ];
        }

        // Update order met imposed path
        $relativePath = 'pdfs/imposed/' . $outputFilename;
        $order->update([
            'pdf_imposed_path' => $relativePath
        ]);

        Log::info("Imposition created for order {$order->order_number}: {$relativePath}");

        return [
            'success' => true,
            'path' => $relativePath,
            'message' => 'Impositie succesvol aangemaakt'
        ];
    }

    /**
     * Check of impositie al bestaat voor een order
     */
    public function hasImposition(Order $order): bool
    {
        if (empty($order->pdf_imposed_path)) {
            return false;
        }

        return Storage::disk('local')->exists($order->pdf_imposed_path);
    }

    /**
     * Haal pad naar geïmponeerde PDF op
     */
    public function getImposedPath(Order $order): ?string
    {
        if (!$this->hasImposition($order)) {
            return null;
        }

        return Storage::disk('local')->path($order->pdf_imposed_path);
    }

    /**
     * Verwijder impositie voor een order
     */
    public function deleteImposition(Order $order): bool
    {
        if (empty($order->pdf_imposed_path)) {
            return true;
        }

        if (Storage::disk('local')->exists($order->pdf_imposed_path)) {
            Storage::disk('local')->delete($order->pdf_imposed_path);
        }

        $order->update(['pdf_imposed_path' => null]);
        
        return true;
    }
}
