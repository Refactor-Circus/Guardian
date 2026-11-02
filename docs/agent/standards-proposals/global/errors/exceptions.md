---
key: errors/exceptions
action: amend
kind: architectural
package: refactor-circus/standards
from_spec: 2026-10-29-2038-guardian-package
status: queued
evidence:
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/build.md Group 4 What I found: 'ScimException promoted public readonly int $status over Keystone PackageException's protected int $status, which is a fatal redeclare'"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/build.md Group 4 Deviations: 'ScimException::$status is public protected(set), not public readonly as in Roster'"
  - "/Users/jay/Projects/packages/Keystone/src/Exceptions/PackageException.php:23 protected int $status = 409"
  - "post-mortem root cause implementer_error: 'a readonly redeclare of ScimException::$status'"
---

## Rationale

A salvaged group 4 commit moved Roster's ScimException with `public readonly int $status`. That is a fatal redeclare of Keystone PackageException's `protected int $status`, and PHPStan flagged it as property.readOnly. The post-mortem lists it as an implementer error that went unverified. The standard mentions overriding $status without saying how, so other packages that move exceptions onto Keystone's base will hit the same thing. The fix Guardian used (`public protected(set)`) is now the documented way.

## Proposed text

# Exceptions

Every exception a package throws descends from one package base, so callers can catch it broadly or narrowly and never see a raw SPL or vendor exception.

## Hierarchy

```
{Package}Exception (extends RuntimeException)        src/Exceptions/
├── {Entity}NotFoundException                        src/Domains/{Domain}/Exceptions/
└── {Domain}FailedException
    └── {Domain}TimeoutException
```

- `{Package}Exception` lives in the top-level `src/Exceptions/` and is shared by every domain. Each subclass lives in its domain's `Exceptions/`; see [[domain-modules]].
- Catch `{Package}Exception` for anything from the package, and narrow to a subclass for a specific condition. A specialised case extends the general one, so either catch works.
- Throw a package subclass, not a bare `\RuntimeException` or `\InvalidArgumentException`.

## Runtime failures

A runtime failure is system state discovered partway through: a record with no published version, a circular reference, an unregistered key. There is no input field to attach an error to, so it gets a typed exception that callers, including host apps embedding the package, can catch.

```php
final class {Entity}NotPublishedException extends {Package}Exception
{
    public static function forModel({Entity}Model $model): self
    {
        return new self("{Entity} [{$model->slug}] has no published version.");
    }
}
```

- Make it `final`, with a static `forX()` named constructor. The message names the stable identifier (a slug or key, not an id) wrapped in `[…]`.
- Carry the context a caller needs to locate the failure as a readonly property (a line number, a key).

## Wrapping third-party exceptions

- The adapter around a vendor client ([[container-bindings]]) wraps every call in `try`/`catch` and rethrows as a package exception. That adapter is the only place vendor exceptions surface.
- The message states the operation and its context (`"Failed to create index [{$name}]: ..."`). Pass `(int) $e->getCode()` and the original exception as `previous` to keep the chain.
- Map only the exact vendor condition you mean. If "does not exist" is only a 404, every other error (auth, connectivity, server) rethrows; reporting those as absence is a bug.

## Not for business rules

Rejected input and broken business rules are not exceptions. Action guards throw a field-keyed `ValidationException` so the response can attach the error to a field; see [[domain-guards]].

## Reference implementation (Refactor Circus)

`{Package}Exception` is `abstract` and extends Keystone's `Exceptions\PackageException` (itself a `RuntimeException`). Its `render()` returns `{"message": ...}` with status `409` by default, so the JSON API and the MCP tools pass the message through as written. Write the message for whoever made the call.

- The base declares `protected int $status = 409`. A subclass changes it by redeclaring the default (`protected int $status = 422;`), or by setting it in its named constructor when the status varies per instance.
- When callers need to read the status, widen it to `public protected(set) int $status`. Never promote it as `public readonly int $status`: redeclaring an inherited mutable property as readonly is a fatal error.

```php
final class ScimException extends GuardianException
{
    public protected(set) int $status;

    public static function forStatus(int $status, string $detail): self
    {
        $e = new self($detail);
        $e->status = $status;

        return $e;
    }
}
```

