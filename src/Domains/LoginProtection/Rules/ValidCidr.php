<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RefactorCircus\Guardian\Domains\LoginProtection\Support\Cidr;

/**
 * An IPv4 or IPv6 address or CIDR range.
 */
final class ValidCidr implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Cidr::valid($value)) {
            $fail(__('guardian::guardian.ip_rule_invalid_cidr'));
        }
    }
}
