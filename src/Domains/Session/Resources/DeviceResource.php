<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * One device a user signs in from, never with its fingerprint.
 *
 * @mixin DeviceModel
 */
final class DeviceResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'name' => $schema->string()->nullable()->description('The browser\'s user agent, as first seen.')->required(),
            'first_seen_at' => $schema->string()->required(),
            'last_seen_at' => $schema->string()->required(),
            'last_ip' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_seen_at' => $this->first_seen_at->toIso8601String(),
            'last_seen_at' => $this->last_seen_at->toIso8601String(),
            'last_ip' => $this->last_ip,
        ];
    }
}
