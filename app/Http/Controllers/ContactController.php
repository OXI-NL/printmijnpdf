<?php

namespace App\Http\Controllers;

use App\Mail\ContactQuestion;
use Illuminate\Http\Request;
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
}
