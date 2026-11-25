<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use RefactorCircus\Guardian\Domains\Password\Actions\CheckPasswordAction;
use RefactorCircus\Guardian\Domains\Password\Data\CheckPasswordData;
use RefactorCircus\Guardian\Domains\Password\Exceptions\PasswordException;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * Check a password against the policy, for anyone (a registration form),
 * with the history of a signed-in caller. Limited to
 * `guardian.passwords.check_per_minute` per user, or per address for
 * guests.
 */
final class CheckPasswordRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    protected function ability(): string
    {
        return 'guardian.security.view';
    }

    public function rules(): array
    {
        return CheckPasswordData::rules();
    }

    public function persist(): JsonResponse
    {
        $this->throttle();

        $user = $this->user();
        $result = app(CheckPasswordAction::class)->execute(CheckPasswordData::fromValidated($this->validated()), $user instanceof Authenticatable ? $user : null);

        return new JsonResponse(['valid' => $result->valid, 'errors' => $result->errors]);
    }

    private function throttle(): void
    {
        $user = $this->user();
        $key = 'guardian-password-check:'.($user instanceof Authenticatable ? 'user:'.(is_scalar($id = $user->getAuthIdentifier()) ? (string) $id : '') : 'ip:'.$this->ip());
        $limit = config('guardian.passwords.check_per_minute', 10);

        if (RateLimiter::tooManyAttempts($key, is_numeric($limit) ? (int) $limit : 10)) {
            throw PasswordException::throttled(RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, 60);
    }
}
