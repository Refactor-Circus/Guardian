<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Support;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Collection;
use Illuminate\Validation\NotPwnedVerifier;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;

/**
 * Laravel's breach check (criterion 40, D-014): the HIBP range API with
 * the first five characters of the password's SHA-1 (k-anonymity) and
 * padding, through Laravel's HTTP client. When HIBP cannot be asked the
 * check passes, as Laravel's does, and Guardian records
 * `password.breach_check_unavailable` (with neither the password nor the
 * prefix). Guardian puts it in place of Laravel's own `NotPwnedVerifier`
 * only; a verifier the host (or a test) binds is used as it is.
 */
final class BreachCheck extends NotPwnedVerifier
{
    public function __construct(?int $timeout = null)
    {
        parent::__construct(app(Factory::class), $timeout);
    }

    /**
     * @param  string  $hashPrefix
     * @return Collection<int, non-falsy-string> the "suffix:count" lines
     */
    protected function search($hashPrefix): Collection
    {
        $response = null;

        try {
            $response = app(Factory::class)->withHeaders(['Add-Padding' => 'true'])->timeout($this->timeout)->get('https://api.pwnedpasswords.com/range/'.$hashPrefix);
        } catch (StrayRequestException $e) {
            // A test that forgot to fake the check must fail, not pass.
            throw $e;
        } catch (Exception $e) {
            report($e);
        }

        if (! $response instanceof Response || ! $response->successful()) {
            $this->unavailable();

            return new Collection;
        }

        return new Collection(array_values(array_filter(explode("\n", trim($response->body())), fn (string $line): bool => str_contains($line, ':'))));
    }

    private function unavailable(): void
    {
        $user = auth()->user();

        app(SecurityLog::class)->record(SecurityEventType::PasswordBreachCheckUnavailable, $user instanceof Authenticatable ? $user : null);
    }
}
