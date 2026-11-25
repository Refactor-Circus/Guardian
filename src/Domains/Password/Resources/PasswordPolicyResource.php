<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\Password\Data\PasswordPolicySettings;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * The active password rules, for a UI to show and check against.
 *
 * @mixin PasswordPolicySettings
 */
final class PasswordPolicyResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'min_length' => $schema->integer()->required(),
            'max_length' => $schema->integer()->required(),
            'mixed_case' => $schema->boolean()->description('Needs upper and lower case letters.')->required(),
            'numbers' => $schema->boolean()->required(),
            'symbols' => $schema->boolean()->required(),
            'letters' => $schema->boolean()->required(),
            'uncompromised' => $schema->boolean()->description('Refused when it appears in a known data breach.')->required(),
            'uncompromised_threshold' => $schema->integer()->description('How many breaches a password may appear in and still pass.')->required(),
            'history' => $schema->integer()->description('How many previous passwords cannot be reused.')->required(),
            'max_age_days' => $schema->integer()->nullable()->description('Days before a password must be changed; null for never.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'min_length' => $this->minLength,
            'max_length' => $this->maxLength,
            'mixed_case' => $this->mixedCase,
            'numbers' => $this->numbers,
            'symbols' => $this->symbols,
            'letters' => $this->letters,
            'uncompromised' => $this->uncompromised,
            'uncompromised_threshold' => $this->uncompromisedThreshold,
            'history' => $this->history,
            'max_age_days' => $this->maxAgeDays,
        ];
    }
}
