<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\OneTimeCodeChannel;

/**
 * A `OneTimeCodeChannel` that keeps what it would send, for tests: email
 * and SMS, or only the types given.
 */
final class FakeOneTimeCodeChannel implements OneTimeCodeChannel
{
    /**
     * @var list<array{type: string, destination: string, code: string, user: int|string}>
     */
    public array $sent = [];

    /**
     * @param  list<string>  $types
     */
    public function __construct(private readonly array $types = ['email', 'sms']) {}

    public function supports(string $type): bool
    {
        return in_array($type, $this->types, true);
    }

    public function send(Authenticatable $user, string $type, string $destination, string $code): void
    {
        $key = $user->getAuthIdentifier();

        $this->sent[] = ['type' => $type, 'destination' => $destination, 'code' => $code, 'user' => is_int($key) ? $key : (string) $key];
    }

    /**
     * The last code sent.
     */
    public function lastCode(): string
    {
        return $this->sent[array_key_last($this->sent)]['code'] ?? '';
    }
}
