<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\ListSecurityEventsAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Concerns\NamesSecurityEvents;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\ListSecurityEventsData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ListMySecurityEventsMcpRequest extends Request
{
    use NamesSecurityEvents;

    protected function ability(): string
    {
        return 'guardian.security.view';
    }

    /**
     * The caller's own events: any signed-in user.
     */
    protected function self(): ?Model
    {
        return $this->actor();
    }

    protected function rules(): array
    {
        return ListSecurityEventsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return SecurityEventResource::collection(app(ListSecurityEventsAction::class)->execute(ListSecurityEventsData::fromValidated(['user' => $this->actorKey()] + $validated)))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
