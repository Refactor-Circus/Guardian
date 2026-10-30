
## Round 1

Revised `spec.md` for the Round 1 findings. Open questions (Q1 to Q5 and the six further ones now listed under Risks) were not decided; where a fix touched one, the spec reports or documents and names the question.

Scope
- Goal: listed the accepted renames (permissions, route names, tab keys, config and env keys, tables, audit package key and events, resource keys), stated there is no automated config or env migration, and said `routes.enabled => false` is Roster's own default carried over. Why: "unchanged" was unclear about which renames are accepted.
- Sources: named Roster `adopt-atrium-screen-registry` and said criteria 16 to 19 cannot be proven until it exists or a stand-in is used.
- Out of scope: added config/env key renaming, grants to existing roles beyond the upgrade, a reversible upgrade `down()`, and removal of Handover and the coexistence tests after Roster `remove-sso-and-scim` is tagged (also noted in criterion 28).
- Criterion 15 tied to behaviour parity; criteria 3 and 9 to 14 left as they were.

Risk
- Criterion 22: planned per table, then drop empty new tables in reverse FK order and rename in forward order; DDL is non-transactional and every step is re-runnable; "never deletes rows that hold data"; grant step in a DB transaction; Roster's only permission tables are `permissions` and `permission_role`; stranded-state report on `about` and in the log; leftover `roster.sso/scim` config listed (no fallback read); tests for all of these, plus a real-Roster-migrations test and `Handover` tests.
- Criterion 21: documented the create-migration `down()` hazard on upgraded installs (README); the behaviour choice stays open.
- Criterion 6: SCIM throttle matches Roster's `roster` limiter (120 a minute, per user or IP), checked in Roster's `RosterServiceProvider`; a 429 test added.
- Criterion 10: Roster stores role scope as `RoleScope` enum values and resource keys are registry-only, so no rename step; implementer re-checks and adds one with a test if that is wrong.
- Criterion 7: `rosterInstalled()` now checks the seam methods and classes and falls back to an "needs a newer" about line; Atrium likewise. Standalone test added.
- Criteria 14 and 15: audit package-key and event-name discontinuity added to README notes and the morph risk.

Testability
- Criterion 8: source pinned to a Roster SHA, committed manifest of moved tests with a case-count test, and a copied-versus-original diff in the PR.
- Criterion 6: literal defaults written out (read from Roster's `config/roster.php`); Keen defaults copied at a pinned SHA.
- Criterion 22: added empty-all-five, mid-failure, count assertions, FK-enforced wording and `Handover` tests. Driver choice left open.
- Criteria 2, 4, 7, 17, 18, 19, 26, 27: replaced PR-listing proofs with Pest tests, added standalone assertions and CI lane location, access-closure 403 proof, test-id list, one-`exists` query-count, concrete slot placement, ten-tool comparison and required README headings.
- Criterion 1: suggest values asserted equal to Roster's at the pinned SHA.
- Not fixed (open): how CI resolves Roster and Atrium branches for the UI criteria.
