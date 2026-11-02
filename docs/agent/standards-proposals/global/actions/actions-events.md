---
key: actions/actions-events
action: amend
kind: architectural
package: refactor-circus/standards
from_spec: 2026-10-29-2038-guardian-package
status: queued
evidence:
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/decisions.md D-041"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/standards-feedback.md row actions/actions-events (read actions dispatch starting/finished events)"
  - "/Users/jay/Projects/packages/Guardian--2026-10-29-2038-guardian-package-record/docs/agent/specs/2026-10-29-2038-guardian-package/round-1/review-standards.md: 'Decision for the user: read actions ... dispatch event pairs, which actions-events.md says they should not' and the nit 'SsoWebController dispatches SsoLoginFailedActionEvent itself ... outside the action'"
  - "/Users/jay/Projects/packages/Keystone/src/Testing/Arch.php actionEventGaps() requires starting and finished events on every scanned file"
  - "post-mortem root cause standards_gap (D-041)"
---

## Rationale

The standard contradicts the tool that enforces it. It says read actions have no events, but Keystone's Arch::actionEventGaps() scans every action file and requires a starting and a finished event, with no exception for reads. Roster's actions and Guardian's moved List*/Show* actions already dispatch the pair. The user decided to keep the events (D-041), which left a standards-feedback row and a review decision. This amendment makes the standard match the code and the arch rule. A review nit found a controller dispatching an action's failed event outside the action, so the amendment also says only the action dispatches its events.

## Proposed text

# Actions & Events

Actions hold business logic. Each one announces itself with an event pair: one event when it starts and, after the data is committed, one when it finishes. Read actions do this too.

## Entry point: `execute()` calls a protected `handle()`

- Actions live in their domain's `Actions/` and are named `{Verb}{Entity}Action`; see [[domain-modules]] and [[naming]].
- Callers call `execute()`. The action implements a **`protected handle(...)`** that `execute()` delegates to. Never make `handle()` public: a single entry point leaves a place to add cross-cutting behavior later.
- No static constructors (`::run()`/`::make()`). Resolve through the container: `app(XAction::class)->execute(...)`.
- Input is an `{Action}Data` object, never an array; see [[data-objects]]. Bound models arrive as further `handle()` parameters.

```php
final class Create{Entity}Action extends Action
{
    protected function handle(Create{Entity}Data $data, ?Model $owner = null): {Entity}Model
    {
        {Entity}CreatingActionEvent::dispatch($data, $owner);

        ${entity} = $this->perform($data, $owner);

        {Entity}CreatedActionEvent::dispatch(${entity});

        return ${entity};
    }

    private function perform(Create{Entity}Data $data, ?Model $owner): {Entity}Model
    {
        return DB::transaction(fn (): {Entity}Model => {Entity}Model::query()->create([...]));
    }
}
```

## Signatures

- **Always a concrete return type, never `mixed`.** Create and update return the model, delete returns `void`, and list returns a paginator or collection.

## Read actions

- Read actions (`List{Entities}Action`, `Show{Entity}Action`) open no transaction, but they still dispatch the pair: `{Entities}ListingActionEvent` with the input and `{Entities}ListedActionEvent` with the result. Audit trails and listeners see every action the same way, and one arch rule covers them all.

```php
protected function handle(List{Entities}Data $data): LengthAwarePaginator
{
    {Entities}ListingActionEvent::dispatch($data);

    $page = {Entity}Model::query()->paginate($data->perPage);

    {Entities}ListedActionEvent::dispatch($page);

    return $page;
}
```

## Transactions & events

- `handle()` dispatches the **starting** event with the input, calls a private `perform()`, dispatches the **finished** event with the result, and returns that result.
- Every write in a mutating `perform()` goes inside `DB::transaction()`, even a single write.
- Finished events dispatch after commit (`ShouldDispatchAfterCommit`). They wait for the outermost transaction and never fire on rollback. An action that throws fires its starting event and no finished event.
- Only the action dispatches its events. Controllers, requests and tools never dispatch an action's event themselves.
- When a caller needs column values after the commit, `return $model->refresh();` **outside** the transaction.

## Dependency injection

- **Do not constructor-inject the model**: pass it through `handle()`. Constructor promotion is only for injected services.

## Events

- Events are `final` classes in the domain's `Events/`, using `Dispatchable` and `SerializesModels`, with no base class. Each is a plain data carrier with promoted constructor properties and may carry more than one value.
- Every action, read or write, has exactly one pair: a starting `{Entity}{Verb-ing}ActionEvent` that carries the input and a finished `{Entity}{Verb-ed}ActionEvent` that carries the result, for example `{Entity}SavingActionEvent` / `{Entity}SavedActionEvent`.
- Action events are distinct from model lifecycle events. Use action events for business logic by default; see [[lifecycle-events]].

## Reference implementation (Refactor Circus)

- Actions extend `RefactorCircus\Keystone\Actions\Action`, whose `execute(mixed ...$args)` forwards to `handle()`.
- Starting events implement `Keystone\Contracts\ActionStartingEvent`. Finished events implement `Keystone\Contracts\ActionFinishedEvent`, which extends `ShouldDispatchAfterCommit`.
- `tests/ArchTest.php` enforces the pairing on every action file, read actions included (`Arch::actionEventGaps()`). It also enforces that writes happen only inside a transaction (`Arch::writesOutsideTransactions()`) and that `handle()` takes no array parameter; see [[arch-tests]].

