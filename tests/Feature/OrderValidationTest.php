<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Validatie van het bestelformulier. Alle gevallen hier falen op validatie,
 * dus de Mollie-aanroep verderop in de controller wordt nooit bereikt.
 */
class OrderValidationTest extends TestCase
{
    use RefreshDatabase;

    private function geldigeBestelling(array $overrides = []): array
    {
        return array_merge([
            'pdf' => UploadedFile::fake()->create('document.pdf', 120, 'application/pdf'),
            'page_count' => 20,
            'format' => 'A4',
            'binding_type' => 'booklet',
            'print_side' => 'double',
            'quantity' => 1,
            'delivery_type' => 'shipping',
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'street' => 'Dorpsstraat',
            'number' => '12',
            'postcode' => '1234 AB',
            'city' => 'Delft',
        ], $overrides);
    }

    public function test_email_met_crlf_wordt_geweigerd(): void
    {
        $this->postJson('/api/order', $this->geldigeBestelling([
            'email' => "jan@example.com\r\nBcc: aanvaller@evil.test",
        ]))->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_ongeldige_postcode_wordt_geweigerd(): void
    {
        $this->postJson('/api/order', $this->geldigeBestelling([
            'postcode' => 'DELFT',
        ]))->assertStatus(422)->assertJsonValidationErrors('postcode');
    }

    public function test_nederlandse_postcode_met_en_zonder_spatie_is_geldig(): void
    {
        foreach (['1234AB', '1234 ab'] as $postcode) {
            $this->postJson('/api/order', $this->geldigeBestelling(['postcode' => $postcode]))
                ->assertJsonMissingValidationErrors('postcode');
        }
    }

    public function test_pdf_is_verplicht(): void
    {
        $data = $this->geldigeBestelling();
        unset($data['pdf']);

        $this->postJson('/api/order', $data)
            ->assertStatus(422)->assertJsonValidationErrors('pdf');
    }

    public function test_onbekend_formaat_wordt_geweigerd(): void
    {
        $this->postJson('/api/order', $this->geldigeBestelling(['format' => 'A3']))
            ->assertStatus(422)->assertJsonValidationErrors('format');
    }

    public function test_onbekende_bindwijze_wordt_geweigerd(): void
    {
        $this->postJson('/api/order', $this->geldigeBestelling(['binding_type' => 'spiraal']))
            ->assertStatus(422)->assertJsonValidationErrors('binding_type');
    }

    public function test_aantal_moet_minimaal_een_zijn(): void
    {
        $this->postJson('/api/order', $this->geldigeBestelling(['quantity' => 0]))
            ->assertStatus(422)->assertJsonValidationErrors('quantity');
    }

    public function test_prijsberekening_vereist_een_pdf(): void
    {
        $this->postJson('/api/calculate-price', [])
            ->assertStatus(422)->assertJsonValidationErrors('pdf');
    }
}
