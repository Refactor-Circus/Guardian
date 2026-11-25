<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\ShowSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Concerns\NamesSecurityEvents;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class ShowSecurityEventRequest extends Request
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

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return (new SecurityEventResource(app(ShowSecurityEventAction::class)->execute($this->securityEvent())))->response();
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
