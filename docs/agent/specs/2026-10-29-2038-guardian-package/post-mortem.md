# Post-mortem: Guardian package (2026-10-29-2038-guardian-package)

## Summary

Guardian is a new package that takes SSO and SCIM out of Roster, with the same behaviour apart from the accepted renames. The factory run `wf_e98c383c-d71` was approved in round 2 on 2026-10-30. PR: Refactor-Circus/Guardian#1. The build ran as seven task groups, then one review round, then a "Group 7" follow-up. The final gate was 546 Pest tests, PHPStan clean, Pint clean and 100% type coverage.

Most of the cost came from groups that needed retries or salvage, and from one Roster-boundary violation (criterion 9) that review round 1 found. No human PR comments or ticket comments exist for this job.

## Metrics

- Rounds: 2 (the run history records `approved` at round 2).
- Task groups: 7. Groups 1 to 4 were retried or salvaged, and group 5 and group 6 each started from earlier attempts' commits.
- Review round 1 findings that were fixed: 1 major (architecture/dependencies, criterion 9) and 2 minor (architecture/service-providers). The re-run of all four reviewers (code, compat, security, standards) passed with nits only.
- Nits that did not start a round: 4 from the standards reviewer, 1 from the security reviewer.
- Decisions: 22 recorded in `decisions.md` (D-020 to D-041; earlier ids are in `shape.md`). Spec review accepted all recommendations. Five came up during build or review: D-037 and D-038 (build, group 2), D-039 (security review), D-040 (code review) and D-041 (standards review).
- Deviations recorded in `build.md`: about 12. They include the `rosterPresent()` public method, `ScimException::$status` visibility, the HandoverPending suite using a stand-in Roster, and `Permissions::forgetCache()` in place of the spec's `forget()`.
- Final test count: 546 passed, 2043 assertions. Type coverage: 100%.
- Not run locally: the `standalone`, `without-atrium`, `mysql` and browser CI jobs.

## What went well

- The spec review (`spec-review.md` round 1) closed many testability gaps early. These included a pinned Roster SHA (`6b1833d`), a moved-tests manifest (`tests/moved-tests.json`) with a case-count test, and literal config defaults. Later groups moved Roster's tests by diffing against that SHA.
- Cross-spec decisions (D-020 to D-022) fixed the Atrium `conflict`, the config-malformation rule and the Roster handover gate before the build started.
- Each group's retry re-checked its work against Roster's sources and did not rewrite it. Group 4's retry caught the Keen symlink that someone had re-linked by hand to an unmerged job branch.
- The gate mapped all 31 criteria to test files. Four reviewers passed on the re-run.
- Security review checked SCIM bearer auth, OIDC verification, injection and bulk limits. It also drove the real behaviour change D-039 (OIDC requires `email_verified`). D-039 shipped with tests (`OidcEmailVerifiedTest`) and a "Changed from Roster" note in the docs, held there by `DocsTest`.

## What cost rounds

- **Boundary violation (round 1 major).** After groups 1 to 6, the implementation still named Roster classes that criterion 9 denies: `ManagesMemberships`, `Support\CaseInsensitive` and `Domains\User\Resources\UserSummaryResource`. The fix added `Domains\Sso\Concerns\JoinsOrganizations`, `ScimQuery::equals()` and Guardian's own `UserSummaryResource`, and widened `RosterBoundaryTest` to cover Roster's `Concerns`, `Resources` and `Support` classes. Until then the arch rule and grep test had missed these classes.
- **Salvage and retry churn.** Groups 1 to 4 were recorded as "already committed, retry changed no code" or "salvaged". Earlier agents left uncommitted work (group 2) or committed it unverified (groups 3 and 4). Group 4 then needed five fixes: a fatal readonly redeclare of `ScimException::$status`, the wrong audit surface (`http` instead of `scim`), a ten-tool test that read the wrong entry points, a stale route-name lookup, and a test asserting an entry Roster never wrote.
- **Roster moving underneath the build.** The linked Roster (`remove-sso-and-scim`) no longer ships SSO and SCIM. Group 5's run showed 26 failures and 18 skips, so group 6 rebuilt fixtures (`tests/Fixtures/roster-sso-scim/`, `tests/Fixtures/roster-6b1833d/`) and changed Group 2 to 4 tests. The spec said to run Roster's own migrations and models, which stopped being possible. Criteria 22 and 28 were met with stand-ins.
- **Late decisions.** D-038 (the `guardian.ui.enabled` key Keystone requires), D-039 and D-040 were each found after the code was written. D-040 changed migration `down()` semantics and four tests.
- **Environment drift.** The Keen symlink was hand-edited, so `PackageWiringTest` failed in group 4's retry. Group 5 could not run the browser suite because `composer update` was off limits.

## Root causes

1. `spec_gap` (criteria 9 and 22, D-024/D-036): The spec said "deny Roster's internal bases and its Atrium/HTTP/MCP layers" but did not list `Concerns`, `Resources` or `Support\*` classes. The first arch test covered only some of the denied namespaces, so three violations got through six groups. Evidence: `build.md` "Review round 1 fixes", `RosterBoundaryTest` widened.
2. `spec_gap` (criteria 22 and 28): The spec assumed the linked Roster would still ship SSO and SCIM for the real-migration and real-`Handover` tests. It did not, and the build recorded two deviations. Evidence: `build.md` Group 6 "Deviations".
3. `spec_gap` (criterion 6, D-038): Keystone's `registerAtriumPlugin()` needs `guardian.ui.enabled`, which criterion 6's key list omitted. Evidence: `build.md` Group 2 "Left" and D-038.
4. `spec_gap` (D-039, D-040): The OIDC `email_verified` gap and the `down()` check scope were not settled in the spec. Both surfaced only in review.
5. `implementer_error`: Group 4's five defects (readonly redeclare, surface registration, test wiring) and group 3's missing organization scope on the connection-created audit entry and unrun Pint. All were fixed within the build, but they came from unverified salvaged commits.
6. `tooling`: Interrupted agents left uncommitted or unverified work in groups 2 to 4 (salvaged and retried). The hand-relinked `vendor/refactor-circus/keen` symlink broke `PackageWiringTest`. The browser dependency could not be installed in the worktree. The salvage and gate steps did not catch either.
7. `standards_gap`: `actions-events.md` says read actions have no events, while Keystone's `Arch::actionEventGaps()` requires a pair on every action (D-041). The standards disagree with each other. Standards-feedback rows were also needed for `booting()` provider registration, the `JoinsOrganizations` departure and the second user summary serializer.
8. `reviewer_noise`: None significant. The four standards nits did not start a round. The gate noted scaffold leftovers as non-blocking, although the code review said they were deleted. That note looks stale, not an actual problem.

## What human review caught

Nothing. The record has no PR review comments and no ticket comments, so no human-found gap can be shown. See `human_review_delta`.

## Proposed follow-ups

- Standards: align `actions-events.md` with `Arch::actionEventGaps()` on read actions (D-041).
- Spec template: when a spec limits another package's API, require a full denied-namespace list (including `Concerns`, `Resources`, `Support\*`) and write the arch test in group 1.
- Spec template: when a spec depends on a sibling branch that another job is changing, such as Roster `remove-sso-and-scim`, pin its fixtures from the start. Do not rely on the live checkout.
- Factory: commit or discard partial work when a group's agent is interrupted. Run the group's tests plus Pint before a salvaged commit counts as done.
- Gate: add a check that `vendor/` sibling symlinks match `composer.local.lock`. Run the browser, `standalone`, `without-atrium` and `mysql` jobs, or record that they are unverified.
- Small items: make `Sso` final, cast `max_payload_bytes`, and give `SsoLoginRefused` a `forX()` named constructor. Re-check the Roster-comparison fixtures once Roster `remove-sso-and-scim` is tagged. Then add the Roster `conflict` entry (D-027) and remove `Handover` and the coexistence tests.
