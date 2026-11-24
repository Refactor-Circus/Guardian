<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian;

use Closure;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Support\Labels;

/**
 * Guardian's entry point for application code, behind the `Guardian` facade.
 * SSO and SCIM are reached through their actions, HTTP API and MCP tools.
 */
class Guardian
{
    public function __construct(private readonly Labels $labels) {}

    /**
     * Name a model of this class, or a subclass, where Guardian's API shows
     * it (a role assignment's `user_label` and `scope_label`, D-053), from a
     * service provider's `boot()`:
     *
     *     Guardian::label(Organization::class, fn (Organization $organization) => $organization->legal_name);
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @param  Closure(TModel): mixed  $label
     */
    public function label(string $class, Closure $label): static
    {
        $this->labels->using($class, $label);

        return $this;
    }
}
