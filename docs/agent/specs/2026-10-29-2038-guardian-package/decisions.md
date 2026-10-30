# Decisions

Earlier D-ids are recorded in shape.md.

## D-020: Suite: proving the package runs without Atrium, and refusing an Atrium too old for the screen registry
- Choice: Prove it both ways: a WithoutAtrium suite inside `composer test`, and a CI lane with Atrium uninstalled. Every package that renders Atrium screens adds a composer `conflict` on refactor-circus/atrium versions older than the screen-extensions registry (Atrium PR #16).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-021: Suite: malformed integration config entries (screen maps, slots) at boot
- Choice: Throw InvalidArgumentException in local and testing. In production, report once, skip the entry and keep booting (same rule as Atrium D-022).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-022: Sequencing with Roster remove-sso-and-scim
- Choice: Use the runtime handover gate now: no SSO/SCIM routes, morph aliases or table rename until Roster's SSO code is gone, and `guardian:upgrade-from-roster` finishes the rename. Add the composer `conflict` on older Roster once the removal is tagged. Without Roster, Guardian is inert, with a clear `about` message.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user (2026-10-29)

## D-023: Q1. Without Roster, what does Guardian do?
- Choice: Inert with a clear message: it boots, `about` says "Inactive: install refactor-circus/roster", and nothing else registers
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-024: Q2. May Guardian use Roster's public domain API, or only the five seams?
- Choice: Allow the seams plus Roster's public domain API, and deny Roster's internal bases and its Atrium/HTTP/MCP layers, enforced by an arch test
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-025: Q3 (D-008). Where is the home for managing SSO connections?
- Choice: The `guardian.sso` tab on `roster.organization`, with the connection screen reached from it, as today
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-026: Q4. What is the default SSO web prefix?
- Choice: Keep `roster/sso` as Guardian's default, with a README note that apps may change it
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-027: Q5. How are this spec and Roster `remove-sso-and-scim` sequenced and gated?
- Choice: Both: the runtime handover gate now, plus the `conflict` entry once the removal is tagged
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-028: Should the transition machinery (Handover, HandoverPending mode, upgrade command and migration) be built in this spec or split into a follow-up?
- Choice: Keep it in this spec, as written, as the last task groups
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-029: When handover is complete but connections are still stranded in roster_* tables, how should Guardian behave?
- Choice: Warn only (about line and reported exception); SSO enforcement stays off until the command runs
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-030: Should Guardian read legacy roster.sso.* / roster.scim.* config values as a fallback?
- Choice: No fallback; detect and report leftovers in the upgrade command
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-031: What should create-migration down() do on an install whose tables came from the Roster rename?
- Choice: Refuse to drop a table that has rows unless forced
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-032: Q1. Without Roster, is Guardian inert (boots with a message, registers nothing) or does it provision onto the app's user model?
- Choice: (a) Inert with a clear message: boots, `about` says "Inactive: install refactor-circus/roster", nothing else registers
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-033: Q5. How are this spec and Roster remove-sso-and-scim sequenced and gated?
- Choice: (c) Both: the runtime gate now, plus the `conflict` entry once the removal is tagged
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-034: Which database drivers do the upgrade and FK tests run against?
- Choice: SQLite plus a MySQL CI lane for the rename and FK-survival cases
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-035: How do the UI criteria (17, 18, 19 and 28) get their Roster and Atrium dependencies in CI?
- Choice: Sequence the UI task group after Roster `adopt-atrium-screen-registry` merges
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-036: Q2. Is Roster's public domain API allowed, or only the five seams?
- Choice: (a) Allow the seams plus Roster's public domain API, deny Roster's internal bases and its Atrium/HTTP/MCP layers (criterion 9)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-037: Support\Integration has a third public method, rosterPresent(), and an injected method_exists check. Keep it?
- Choice: Keep rosterPresent() public and the injected method check, and mark rosterPresent() @internal.
- Rationale: The about line must tell "no Roster" from "Roster without the seams"; the Standalone test can only stub a seam-less Roster through the injected check.
- Date: 2026-10-30
- Raised by: build (group 2)

## D-038: How does group 5 register GuardianPlugin, given Keystone's registerAtriumPlugin() needs guardian.ui.enabled?
- Choice: Add 'ui' => ['enabled' => true] to config/guardian.php and call registerAtriumPlugin(GuardianPlugin::class) behind atriumInstalled().
- Rationale: Same path as every other suite package.
- Date: 2026-10-30
- Raised by: build (group 2)
