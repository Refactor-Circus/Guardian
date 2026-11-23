<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\StartOneTimeCodeEnrolmentData;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\OneTimeCodeEnrolmentStartedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\OneTimeCodeEnrolmentStartingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\OneTimeCodes;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class StartOneTimeCodeEnrolmentAction extends Action
{
    public function __construct(private readonly OneTimeCodes $codes) {}

    /**
     * Enrol an email address or phone number and send it a code (criterion
     * 29), replacing an enrolment of that type still waiting. Calling it
     * again resends, at most once a minute. A type the bound channel cannot
     * send is refused with `method_unavailable`.
     */
    protected function handle(Model&Authenticatable $user, StartOneTimeCodeEnrolmentData $data): TwoFactorMethodModel
    {
        OneTimeCodeEnrolmentStartingActionEvent::dispatch($user, $data->type);

        $type = TwoFactorMethodType::from($data->type);

        if (! $this->codes->supports($type->value)) {
            throw TwoFactorException::methodUnavailable($type->value);
        }

        $this->codes->ensureCanSend($user);
        $key = UserKeys::of($user);

        $method = DB::transaction(function () use ($key, $type, $data): TwoFactorMethodModel {
            TwoFactorMethodModel::query()->ownedBy($key)->where('type', $type->value)->whereNull('confirmed_at')->get()->each->delete();

            return TwoFactorMethodModel::query()->create([
                'user_id' => $key,
                'type' => $type,
                'destination' => $data->destination,
                'label' => $data->label,
            ]);
        });

        $this->codes->send($user, $method);

        OneTimeCodeEnrolmentStartedActionEvent::dispatch($method);

        return $method;
    }
}
