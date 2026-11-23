<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\SendChallengeCodeData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\ChallengeCodeSendingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\ChallengeCodeSentActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\OneTimeCodes;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class SendChallengeCodeAction extends Action
{
    public function __construct(private readonly OneTimeCodes $codes) {}

    /**
     * Send a challenge code to the user's confirmed email address or phone
     * (criterion 32), at most once a minute; a type the bound channel cannot
     * send answers `method_unavailable`. Sending writes only the code's
     * HMAC, replacing any code still waiting.
     */
    protected function handle(Model&Authenticatable $user, SendChallengeCodeData $data): void
    {
        ChallengeCodeSendingActionEvent::dispatch($user, $data->method);

        if (! $this->codes->supports($data->method)) {
            throw TwoFactorException::methodUnavailable($data->method);
        }

        $method = TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))->confirmed()->where('type', $data->method)->oldest()->first()
            ?? throw ValidationException::withMessages(['method' => __('guardian::guardian.two_factor_no_such_method')]);

        $this->codes->send($user, $method);

        ChallengeCodeSentActionEvent::dispatch($user, $data->method);
    }
}
