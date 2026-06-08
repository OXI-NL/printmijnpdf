<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Workaround for CVE-2026-48019: CRLF injection in Laravel's default email rule.
 * Rejects email addresses containing CR/LF characters that could be used
 * for header injection attacks.
 */
class SafeEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Block CRLF characters (CR = \r, LF = \n)
        if (preg_match('/[\r\n]/', $value)) {
            $fail('Het e-mailadres bevat ongeldige tekens.');
        }
    }
}
