<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\ListSecurityEventsAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Concerns\NamesSecurityEvents;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\ListSecurityEventsData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ListSecurityEventsMcpRequest extends Request
{
    use NamesSecurityEvents;

    protected function ability(): string
    {
        return 'guardian.security.view';
    }

    protected function rules(): array
    {
        return ListSecurityEventsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return SecurityEventResource::collection(app(ListSecurityEventsAction::class)->execute(ListSecurityEventsData::fromValidated($validated)))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
