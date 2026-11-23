<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures;

use Illuminate\Contracts\Validation\UncompromisedVerifier;

/**
 * An `UncompromisedVerifier` that calls no one: the passwords given are
 * breached, every other one is not. It keeps what it was asked.
 */
final class FakeUncompromisedVerifier implements UncompromisedVerifier
{
    /**
     * @var list<int>
     */
    public array $thresholds = [];

    /**
     * @param  list<string>  $breached
     */
    public function __construct(private readonly array $breached = []) {}

    /**
     * @param  array{value: mixed, threshold: int}  $data
     */
    public function verify($data): bool
    {
        $this->thresholds[] = $data['threshold'];

        return ! in_array($data['value'], $this->breached, true);
    }
}
