<?php

namespace Tests\Feature;

use App\Mail\ContactQuestion;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    public function test_geldige_vraag_stuurt_mail_naar_printmijnpdf_en_kopie_naar_klant(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'question' => 'Kunnen jullie ook A3 printen?',
        ])->assertOk()->assertJson(['success' => true]);

        Mail::assertSent(ContactQuestion::class, 2);
        Mail::assertSent(ContactQuestion::class, fn ($mail) => $mail->hasTo('info@printmijnpdf.nl'));
        Mail::assertSent(ContactQuestion::class, fn ($mail) => $mail->hasTo('jan@example.com'));
    }

    public function test_email_met_crlf_wordt_geweigerd_en_verstuurt_niets(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'name' => 'Jan Jansen',
            'email' => "jan@example.com\r\nBcc: aanvaller@evil.test",
            'question' => 'Test',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }

    public function test_lege_vraag_wordt_geweigerd(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
        ])->assertStatus(422)->assertJsonValidationErrors('question');

        Mail::assertNothingSent();
    }

    public function test_te_lange_vraag_wordt_geweigerd(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'question' => str_repeat('a', 2001),
        ])->assertStatus(422)->assertJsonValidationErrors('question');

        Mail::assertNothingSent();
    }
}
