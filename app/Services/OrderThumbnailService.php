<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Kleine PNG van pagina 1 voor de bevestigingsmail. De browser rendert die met
 * pdf.js (de server heeft geen PDF-rasteraar); hier wordt hij met GD opnieuw
 * opgebouwd, zodat er alleen schone pixels van een begrensde maat overblijven.
 */
class OrderThumbnailService
{
    public const MAX_WIDTH = 240;
    public const MAX_HEIGHT = 480;
    private const MAX_UPLOAD_BYTES = 2 * 1024 * 1024;
    private const MAX_SOURCE_PX = 2000;

    /** Pad van de thumbnail naast de klant-PDF: pdfs/x.pdf → pdfs/x-thumb.png */
    public static function pathFor(Order $order): ?string
    {
        return $order->pdf_path ? preg_replace('/\.pdf$/i', '', $order->pdf_path) . '-thumb.png' : null;
    }

    /** Bestaand thumbnail-pad, of null als er geen is */
    public static function existingPath(Order $order): ?string
    {
        $path = self::pathFor($order);

        return $path && Storage::disk('local')->exists($path) ? $path : null;
    }

    /**
     * Bewaar de PNG uit de browser. Mislukt dat, dan gaat de bestelling gewoon
     * door; de mail komt dan zonder thumbnail.
     */
    public static function store(Order $order, ?UploadedFile $file): ?string
    {
        $path = self::pathFor($order);
        if (!$file || !$path || !$file->isValid() || $file->getSize() > self::MAX_UPLOAD_BYTES) {
            return null;
        }

        try {
            // Eerst de afmetingen, zodat een klein bestand met enorme pixelmaten GD niet opblaast
            $info = @getimagesize($file->getRealPath());
            if (!$info || $info[2] !== IMAGETYPE_PNG || $info[0] > self::MAX_SOURCE_PX || $info[1] > self::MAX_SOURCE_PX) {
                return null;
            }

            $source = @imagecreatefrompng($file->getRealPath());
            if (!$source) {
                return null;
            }

            $w = imagesx($source);
            $h = imagesy($source);
            $scale = min(1, self::MAX_WIDTH / $w, self::MAX_HEIGHT / $h);
            $tw = max(1, (int) round($w * $scale));
            $th = max(1, (int) round($h * $scale));

            $thumb = imagecreatetruecolor($tw, $th);
            imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $tw, $th, $w, $h);

            ob_start();
            imagepng($thumb, null, 9);
            Storage::disk('local')->put($path, ob_get_clean());

            return $path;
        } catch (\Throwable $e) {
            Log::warning("Thumbnail voor bestelling {$order->order_number} niet opgeslagen: " . $e->getMessage());

            return null;
        }
    }

    public static function delete(Order $order): void
    {
        if ($path = self::existingPath($order)) {
            Storage::disk('local')->delete($path);
        }
    }
}
