<?php

namespace App\Http\Controllers;

use App\Mail\ContactQuestion;
use App\Mail\CustomFormatRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'question' => 'required|string|max:2000',
        ]);

        // Mail naar PrintMijnPDF
        Mail::to('info@printmijnpdf.nl')
            ->send(new ContactQuestion(
                $validated['name'],
                $validated['email'],
                $validated['question'],
                isCustomerCopy: false,
            ));

        // Kopie naar klant
        Mail::to($validated['email'])
            ->send(new ContactQuestion(
                $validated['name'],
                $validated['email'],
                $validated['question'],
                isCustomerCopy: true,
            ));

        return response()->json(['success' => true]);
    }

    public function sendCustomFormat(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'pdf' => 'required|file|mimes:pdf|max:102400',
        ], [
            'name.required' => 'Vul je naam in.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'phone.required' => 'Vul je telefoonnummer in.',
            'pdf.required' => 'Upload je PDF-bestand.',
            'pdf.mimes' => 'Alleen PDF-bestanden zijn toegestaan.',
            'pdf.max' => 'Het bestand is te groot (max 100 MB).',
        ]);

        try {
            $pdf = $request->file('pdf');
            $originalName = $pdf->getClientOriginalName();
            $storedName = 'custom_' . time() . '_' . uniqid() . '.pdf';
            $path = $pdf->storeAs('pdfs/custom-format', $storedName, 'local');

            // Mail naar PrintMijnPDF (met PDF-bijlage)
            Mail::to('info@printmijnpdf.nl')
                ->send(new CustomFormatRequest(
                    $validated['name'],
                    $validated['email'],
                    $validated['phone'],
                    isCustomerCopy: false,
                    pdfPath: $path,
                    pdfOriginalName: $originalName,
                ));

            // Kopie naar klant (zonder bijlage)
            Mail::to($validated['email'])
                ->send(new CustomFormatRequest(
                    $validated['name'],
                    $validated['email'],
                    $validated['phone'],
                    isCustomerCopy: true,
                ));

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Custom format request failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Er ging iets mis bij het verzenden. Probeer het opnieuw.',
            ], 500);
        }
    }
}
