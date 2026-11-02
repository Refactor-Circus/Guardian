---
key: http/resources
action: amend
kind: architectural
package: refactor-circus/standards
from_spec: 2026-10-29-2038-guardian-package
status: queued
evidence:
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/standards-feedback.md row http/resources (second UserSummaryResource serializer)"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/build.md Review round 1 fixes: 'SsoIdentityResource nests Guardian's own Domains\\Sso\\Resources\\UserSummaryResource, with the same schema and payload' and UserSummaryResourceTest"
  - "post-mortem root cause spec_gap (criterion 9 denied namespaces included Resources)"
---

## Rationale

Under the package boundary (criterion 9), Guardian could not use Roster's UserSummaryResource but had to nest a user in SsoIdentityResource. The standard forbids a second serializer outright, so the fix needed an http/resources feedback row. Guardian's answer, a mirror held equal by a schema and payload parity test, is the safe pattern. Writing it down settles the conflict between http/resources and the dependencies boundary for every add-on package.

## Proposed text

# Resources

Every payload a package returns serializes through a JSON resource, and every surface uses the same resource, so a payload is identical wherever it is served.

## Placement

- A model's resource sits beside the model, in `src/Domains/{Domain}/Resources/` next to `Models/` ([[domain-modules]]).
- A payload with no model behind it lives in `src/Domains/{Domain}/Http/Resources/`, or in `src/Http/Resources/` when several domains share it.
- Name it `{Entity}Resource` ([[naming]]).

## Shape

```php
/**
 * @mixin ProjectModel
 */
final class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'owner' => new OwnerResource($this->whenLoaded('owner')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- Make it `final`, with a `@mixin` docblock naming the model for static analysis.
- Expose the public identity (slug or route key). Leave internal ids out unless there is a concrete need.
- Nest relations only through `whenLoaded()`. The action or request eager-loads what the resource needs, so a resource never triggers queries.
- Write timestamps as `?->toIso8601String()`.
- No conditionals on request context: the same model always yields the same payload.

## One resource, every surface

- HTTP responses, console output and any other surface (a tool server, a UI's data endpoint) return the same resource, never a hand-built array. A second serializer for the same model is drift waiting to happen.
- Lists use the resource's collection, with paging metadata beside the data.

## Another package's model

- A resource belongs to its package's HTTP layer, so another package may not use it; see [[dependencies]].
- When you must nest another package's model, mirror its resource in your own domain's `Resources/` and read the model only through that package's public API. Add a test that holds the mirror's `schema()` and payload equal to the original, so the two cannot drift unnoticed. Record the mirror in `standards-feedback.md`.

```php
it('serializes a user exactly as {Peer} does', function (): void {
    $user = {Peer}UserFactory::new()->create();

    expect((new UserSummaryResource($user))->resolve())
        ->toBe((new {Peer}UserSummaryResource($user))->resolve());
});
```

## Reference implementation (Refactor Circus)

Resources extend `RefactorCircus\Keystone\Http\Resources\JsonResource`, whose collections are `Keystone\Http\Resources\ResourceCollection`. The base answers HTTP through Laravel's `toResponse()` and other surfaces through `toStructuredResponse()` with the same payload, and declares the payload's fields through `schema()`. Guardian's `Domains\Sso\Resources\UserSummaryResource` and `tests/Feature/Sso/UserSummaryResourceTest.php` are the reference mirror.

