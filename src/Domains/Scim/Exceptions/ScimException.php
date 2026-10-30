<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Exceptions;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Scim\Services\Scim;
use RefactorCircus\Guardian\Exceptions\GuardianException;

/**
 * A SCIM error (RFC 7644 §3.12), rendered in the SCIM error schema.
 */
final class ScimException extends GuardianException
{
    public function __construct(
        public readonly int $status,
        string $detail,
        public readonly ?string $scimType = null,
    ) {
        parent::__construct($detail);
    }

    public static function notFound(string $what = 'Resource'): self
    {
        return new self(404, "{$what} not found.");
    }

    public static function invalidFilter(string $detail): self
    {
        return new self(400, $detail, 'invalidFilter');
    }

    public static function invalidPath(string $path): self
    {
        return new self(400, "Unsupported path [{$path}].", 'invalidPath');
    }

    public static function invalidValue(string $detail): self
    {
        return new self(400, $detail, 'invalidValue');
    }

    public static function uniqueness(string $detail): self
    {
        return new self(409, $detail, 'uniqueness');
    }

    public static function mutability(string $detail): self
    {
        return new self(409, $detail, 'mutability');
    }

    public static function preconditionFailed(): self
    {
        return new self(412, 'The resource has changed since the version given in If-Match.');
    }

    public function render(): JsonResponse
    {
        return Scim::response(array_filter([
            'schemas' => [Scim::ERROR],
            'status' => (string) $this->status,
            'scimType' => $this->scimType,
            'detail' => $this->getMessage(),
        ], fn (mixed $value): bool => $value !== null), $this->status);
    }
}
