<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Support\Users;

/**
 * A user's identity alone, for nesting inside Guardian's payloads.
 *
 * The same fields as Roster's user summary, read through Roster's public
 * `Support\Users`, so a user nested in an SSO identity looks as it does in
 * Roster's own payloads (`tests/Feature/Sso/UserSummaryResourceTest.php`).
 *
 * @property Model $resource
 */
final class UserSummaryResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->union(['integer', 'string'])->description('The user\'s route key.')->required(),
            'name' => $schema->string()->nullable()->required(),
            'email' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $users = app(Users::class);

        return [
            'id' => $this->resource->getRouteKey(),
            'name' => $users->name($this->resource),
            'email' => $users->email($this->resource),
        ];
    }
}
