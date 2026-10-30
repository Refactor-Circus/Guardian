VERDICT: PASS

Standards review, round 1 (re-run on the current diff). The earlier majors are fixed: the Roster-boundary classes were replaced or recorded in standards-feedback.md, views are publishable with the guardian and guardian-views tags, and the booting() registration of DomainServiceProvider has a feedback row.

No finding at minor or above. The nits below start no round.

- nit (errors/exceptions): Domains/Sso/Exceptions/SsoLoginRefused.php has a public constructor, not a forX() named constructor.
- nit (architecture/configuration): Domains/Scim/Services/Bulk.php interpolates an uncast config('guardian.scim.bulk.max_payload_bytes') into the 413 message. The other reads cast.
- nit (actions/actions-events): SsoWebController dispatches SsoLoginFailedActionEvent itself for an invalid provider response, outside the action. SsoLoginAction also has a third (failed) event beside the starting/finished pair.
- nit (architecture/public-api): Domains/Sso/Services/Sso.php is not final. Make it final unless tests need to subclass it.

Decision for the user: read actions (List*, ShowSsoConnection) dispatch event pairs, which actions-events.md says they should not. Keystone's Arch::actionEventGaps() requires a pair from every action, and Roster behaved the same. Recommend keeping them.
