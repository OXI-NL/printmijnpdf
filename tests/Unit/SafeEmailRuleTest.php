<?php

namespace Tests\Unit;

use App\Rules\SafeEmail;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Workaround voor CVE-2026-48019 (CRLF injection in Laravels email-regel).
 * Laravel 11.x kreeg hiervoor geen patch; deze regel blokkeert CR/LF zelf.
 */
class SafeEmailRuleTest extends TestCase
{
    private function passes(string $email): bool
    {
        return Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'max:255', new SafeEmail]],
        )->passes();
    }

    public function test_gewoon_adres_wordt_geaccepteerd(): void
    {
        $this->assertTrue($this->passes('klant@example.com'));
        $this->assertTrue($this->passes('voor.naam+tag@sub.example.nl'));
    }

    public function test_crlf_header_injection_wordt_geblokkeerd(): void
    {
        $this->assertFalse($this->passes("klant@example.com\r\nBcc: aanvaller@evil.test"));
    }

    public function test_losse_line_feed_wordt_geblokkeerd(): void
    {
        $this->assertFalse($this->passes("klant@example.com\nBcc: aanvaller@evil.test"));
    }

    public function test_losse_carriage_return_wordt_geblokkeerd(): void
    {
        $this->assertFalse($this->passes("klant@example.com\r"));
    }

    public function test_ongeldig_adres_wordt_alsnog_geblokkeerd(): void
    {
        $this->assertFalse($this->passes('geen-email-adres'));
    }

    public function test_regel_geeft_een_nederlandse_foutmelding(): void
    {
        $v = Validator::make(
            ['email' => "a@b.com\r\nBcc: x@y.z"],
            ['email' => [new SafeEmail]],
        );

        $this->assertFalse($v->passes());
        $this->assertStringContainsString('ongeldige tekens', $v->errors()->first('email'));
    }
}
