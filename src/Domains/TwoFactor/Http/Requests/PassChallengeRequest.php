<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\PassChallengeAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\PassChallengeData;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\TwoFactorSession;

/**
 * Answer the challenge: 204, the session confirmed under a new id.
 */
final class PassChallengeRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return PassChallengeData::rules();
    }

    public function persist(): JsonResponse
    {
        if (! $this->hasSession()) {
            throw new TwoFactorException(__('guardian::guardian.two_factor_session_required'), 'session_required', 409);
        }

        app(PassChallengeAction::class)->execute($this->signedIn(), PassChallengeData::fromValidated($this->validated()));
        app(TwoFactorSession::class)->confirm($this->session(), $this->signedIn());

        return new JsonResponse(null, 204);
    }
}
