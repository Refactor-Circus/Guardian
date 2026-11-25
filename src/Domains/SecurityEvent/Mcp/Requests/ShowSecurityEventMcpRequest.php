<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\ShowSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Concerns\NamesSecurityEvents;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ShowSecurityEventMcpRequest extends Request
{
    use NamesSecurityEvents;

    protected function ability(): string
    {
        return 'guardian.security.view';
    }

    /**
     * Everyone may read their own events.
     */
    protected function self(): ?Model
    {
        return $this->securityEvent()->user;
    }

    protected function rules(): array
    {
        return ['event' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new SecurityEventResource(app(ShowSecurityEventAction::class)->execute($this->securityEvent())))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
