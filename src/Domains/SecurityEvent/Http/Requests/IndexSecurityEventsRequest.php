<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\ListSecurityEventsAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Concerns\NamesSecurityEvents;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\ListSecurityEventsData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class IndexSecurityEventsRequest extends Request
{
    use NamesSecurityEvents;

    protected function ability(): string
    {
        return 'guardian.security.view';
    }

    public function rules(): array
    {
        return ListSecurityEventsData::rules();
    }

    public function persist(): JsonResponse
    {
        return SecurityEventResource::collection(app(ListSecurityEventsAction::class)->execute(ListSecurityEventsData::fromValidated($this->validated())))->response();
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
