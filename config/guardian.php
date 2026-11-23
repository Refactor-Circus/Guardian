<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Permission\Policies\PermissionPolicy;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Policies\RoleAssignmentPolicy;
use RefactorCircus\Guardian\Domains\Role\Policies\RolePolicy;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimGroupPolicy;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimTokenPolicy;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimUserPolicy;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Policies\SecurityEventPolicy;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoIdentityPolicy;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\OneTimeCodeModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\RecoveryCodeModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Policies\TwoFactorMethodPolicy;

/*
 * Guardian is the suite's security package. Only SSO and SCIM build on
 * refactor-circus/roster's users and organizations: without Roster they are
 * skipped, `php artisan about` says so, and everything else works.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On
    |--------------------------------------------------------------------------
    |
    | Organizations can sign their people in through their own identity
    | provider (OIDC, SAML or Microsoft Entra ID), configured per
    | organization via the API or MCP. Requires the optional package
    | laravel/socialite plus, per protocol, firebase/php-jwt (OIDC),
    | socialiteproviders/saml2 (SAML) or socialiteproviders/microsoft-azure
    | (Entra ID).
    |
    | redirect:                Where a successful sign-in lands.
    | discovery_cache_seconds: How long OIDC discovery documents and signing
    |                          keys are cached.
    | prefix / middleware:     Where the sign-in routes live. `roster/sso` is
    |                          kept from Roster so identity providers' callback
    |                          URLs keep working; change it if you like, and
    |                          update the URLs at each identity provider.
    |
    | SECURITY: serve the callback over HTTPS. An identity provider is only
    | trusted for its organization's own domains: new accounts and automatic
    | linking happen only for those emails.
    |
    */

    'sso' => [
        'redirect' => '/',
        'discovery_cache_seconds' => 3600,
        'prefix' => 'roster/sso',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | SCIM Provisioning
    |--------------------------------------------------------------------------
    |
    | A SCIM 2.0 server per organization at /{prefix}/{organization}, so
    | identity providers (Okta, Microsoft Entra ID, ...) can create, update
    | and deprovision its members (Users) and teams (Groups).
    |
    | enabled:                Serve the SCIM endpoints at all.
    | middleware:             `throttle:guardian` is the limiter under Rate
    |                         Limiting below.
    | default_count:          Results per page when the client asks for none.
    | max_count:              The most results one page returns.
    | bulk.max_operations:    The most operations one Bulk request may carry.
    | bulk.max_payload_bytes: The largest Bulk request body accepted.
    | audit_context_packages: Package keys whose audit entries record the
    |                         SCIM token a change came through.
    |
    | Each request needs one of the organization's SCIM tokens (issued via
    | the API or MCP, shown once, revocable). SECURITY: a token can
    | change the organization's membership; treat it like a password. It
    | only ever reaches its own organization, and only claims existing
    | accounts on the organization's own domains.
    |
    */

    'scim' => [
        'enabled' => true,
        'prefix' => 'scim/v2',
        'middleware' => ['api', 'throttle:guardian'],
        'default_count' => 100,
        'max_count' => 500,
        'bulk' => [
            'max_operations' => 100,
            'max_payload_bytes' => 1048576,
        ],
        'audit_context_packages' => ['guardian', 'roster'],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP API Routes
    |--------------------------------------------------------------------------
    |
    | The JSON API for managing SSO connections, linked identities and SCIM
    | tokens. Disabled by default.
    |
    | SECURITY: each route checks the caller's permissions (see
    | Authorization below), but it needs an authenticated user to check: add
    | authentication middleware (e.g. `auth:sanctum`) before enabling it.
    |
    */

    'routes' => [
        'enabled' => false,
        'prefix' => 'guardian',
        'middleware' => ['api', 'throttle:guardian'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | Every API route and MCP tool checks the caller's abilities (such as
    | `guardian.sso.manage`) in the scope it is about, through Keystone's
    | permissions contract: whichever role store is bound answers, or the
    | host's Gate when none is.
    |
    | enabled: Set to false to leave the route middleware as the only check,
    |          as suits an operator API or a trusted server-to-server
    |          integration. SECURITY: anyone the middleware lets in may then
    |          do anything.
    | bind:    Guardian is the role store: it answers Keystone's
    |          `Permissions` and `Roles` for every installed package from
    |          its role tables, and Laravel's Gate for super-admins and the
    |          abilities it knows. Set to false to keep Keystone's defaults
    |          (the Gate decides, and no roles are stored).
    |
    */

    'authorization' => [
        'enabled' => true,
        'bind' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admins
    |--------------------------------------------------------------------------
    |
    | Emails of users who may do anything, everywhere, without holding a
    | super role: a way back in when nobody holds one. Each counts only once
    | its email is verified.
    |
    | SECURITY: list only addresses you control, and keep the list short.
    |
    */

    'super_admins' => [],

    /*
    |--------------------------------------------------------------------------
    | Permission Cache
    |--------------------------------------------------------------------------
    |
    | Answers are remembered for the request. With a TTL, the permission
    | names and each user's grants are kept in a cache store between
    | requests too; Guardian forgets them whenever it changes a role,
    | assignment or permission.
    |
    | ttl:   Seconds to keep them; 0 turns the cache off.
    | store: The cache store; null uses the default store. Use a store every
    |        server shares, or changes reach other servers only on expiry.
    |
    */

    'permission_cache' => [
        'ttl' => 0,
        'store' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | The `throttle:guardian` limiter in the default API and SCIM middleware:
    | requests per minute, per signed-in user (or per IP for guests). Null
    | turns the limiter off; you can also drop it from the middleware lists.
    |
    */

    'rate_limit' => [
        'per_minute' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditing
    |--------------------------------------------------------------------------
    |
    | Guardian keeps no audit log of its own. With refactor-circus/keen
    | installed, Keen records every change Guardian makes, as package
    | `guardian`. Without Keen none of that exists.
    |
    | enabled: Set to false to keep Guardian out of the audit log even while
    |          Keen is installed.
    |
    */

    'audit' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | The user model every domain but SSO and SCIM works with (those use
    | Roster's `roster.users.*`).
    |
    | model:    The Eloquent user model. Null uses the `users` auth
    |           provider's model (`auth.providers.users.model`).
    | key_type: The type of your users' primary key, for the columns that
    |           point at users: `int`, `ulid` or `uuid`. Set it before
    |           running the migrations.
    |
    */

    'users' => [
        'model' => null,
        'key_type' => 'int',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    |
    | built_in: The roles `php artisan guardian:sync-permissions` (also run
    |           by the role tables migration) creates when they are missing,
    |           by slug. `scope` is where the role may be assigned: `global`
    |           (only with no scope), `any`, or a scope model's morph type.
    |           A `super` role may do anything. Each role is granted the
    |           abilities whose packages name it (`grantTo`) only when it is
    |           created, so your edits survive every sync.
    |
    | SECURITY: holders of a super role may do anything, everywhere.
    |
    */

    'roles' => [
        'built_in' => [
            'super-admin' => ['name' => 'Super admin', 'scope' => 'global', 'super' => true],
            'admin' => ['name' => 'Admin', 'scope' => 'any', 'super' => false],
            'member' => ['name' => 'Member', 'scope' => 'any', 'super' => false],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Adopting Roster's Roles
    |--------------------------------------------------------------------------
    |
    | Roster v0.1 kept roles, permissions and assignments in its own tables.
    | The `adopt_roster_role_tables` migration (and `php artisan
    | guardian:adopt-roster-roles`) copies them into Guardian's once, while
    | Guardian holds nothing but its seeded built-in roles, and never alters
    | or drops Roster's tables: drop them by hand once you are done.
    |
    | roster_tables: Where Roster kept them (its `roster.tables.*` names).
    | scope_types:   The morph type Roster's `organization` and `team`
    |                scopes become: a model class, or the alias your morph
    |                map gives it. Set these before migrating when you map
    |                Roster's models to aliases.
    |
    */

    'adopt' => [
        'roster_tables' => [
            'permissions' => 'roster_permissions',
            'roles' => 'roster_roles',
            'permission_role' => 'roster_permission_role',
            'role_assignments' => 'roster_role_assignments',
        ],
        'scope_types' => [
            'organization' => 'RefactorCircus\\Roster\\Domains\\Organization\\Models\\OrganizationModel',
            'team' => 'RefactorCircus\\Roster\\Domains\\Team\\Models\\TeamModel',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Log
    |--------------------------------------------------------------------------
    |
    | Guardian records security events (sign-ins, failures, lockouts,
    | sign-outs, password resets, and the two-factor, session, token and IP
    | events of its other domains) in its own table, readable through the
    | `guardian.security-events.*` routes and MCP tools with
    | `guardian.security.view`; every signed-in user may read their own.
    |
    | enabled:        Record Laravel's Login, Failed, Lockout, Logout and
    |                 PasswordReset events. Guardian's own events are always
    |                 recorded.
    | retention_days: Rows older than this are pruned by `php artisan
    |                 model:prune` (schedule it). Null keeps them forever.
    | identifier_keys: The credential keys a failed sign-in's identifier is
    |                 read from, in order.
    |
    | SECURITY: a failed attempt's identifier is stored only as an HMAC
    | keyed by the app key, never as typed; passwords, codes, secrets and
    | tokens are never stored.
    |
    */

    'security_log' => [
        'enabled' => true,
        'retention_days' => 90,
        'identifier_keys' => ['email', 'username'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Users enrol an authenticator app (TOTP) or an email address or phone
    | number codes are sent to, and get ten single-use
    | recovery codes with their first factor, through the
    | `guardian.two-factor.*` routes. Secrets are encrypted at rest, codes
    | are kept only as HMACs, and a TOTP code is never accepted twice.
    |
    | required: Require two-factor of every user. With the
    |           `guardian.two-factor` middleware on your routes, a user
    |           without a factor is refused until they enrol.
    | issuer:   The name authenticator apps show; null uses `app.name`.
    | one_time_codes.ttl_minutes: How long an email or SMS code is valid.
    |           Each code is six digits, single use, with five attempts;
    |           at most one is sent per minute per user. Email codes go out
    |           as a mail notification; bind your own `OneTimeCodeChannel`
    |           to send SMS.
    | passkeys: Need web-auth/webauthn-lib (without it every passkey
    |           endpoint answers 501). `rp_id` is the relying party id
    |           (null: the host of `app.url`), `rp_name` the name shown
    |           (null: `app.name`), and `origins` the origins a browser
    |           may answer from (null: the origin of `app.url`).
    |
    | SECURITY: removing a factor needs a code from another one, or a
    | recovery code; an admin with `guardian.two-factor.manage` can reset a
    | user who lost theirs.
    |
    */

    'two_factor' => [
        'required' => false,
        'issuer' => null,
        'one_time_codes' => [
            'ttl_minutes' => 10,
        ],
        'passkeys' => [
            'rp_id' => null,
            'rp_name' => null,
            'origins' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | The policy the Gate uses for each Guardian model. Point a model at a
    | class of your own (extending the bundled one, or not) to change who may
    | do what. The bundled policies answer with Keystone's permissions
    | contract: `guardian.sso.*` and `guardian.scim.manage` in the record's
    | organization, `guardian.roles.*` in the scope a call is about.
    |
    */

    'policies' => [
        SsoConnectionModel::class => SsoConnectionPolicy::class,
        SsoIdentityModel::class => SsoIdentityPolicy::class,
        ScimTokenModel::class => ScimTokenPolicy::class,
        ScimUserModel::class => ScimUserPolicy::class,
        ScimGroupModel::class => ScimGroupPolicy::class,
        RoleModel::class => RolePolicy::class,
        RoleAssignmentModel::class => RoleAssignmentPolicy::class,
        PermissionModel::class => PermissionPolicy::class,
        SecurityEventModel::class => SecurityEventPolicy::class,
        TwoFactorMethodModel::class => TwoFactorMethodPolicy::class,
        RecoveryCodeModel::class => TwoFactorMethodPolicy::class,
        OneTimeCodeModel::class => TwoFactorMethodPolicy::class,
        PasskeyModel::class => TwoFactorMethodPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | The table each of Guardian's records is stored in. Set these before
    | running the migrations: the migrations, models, validation rules and
    | queries all read them. Keys left out keep their default.
    |
    */

    'tables' => [
        'sso_connections' => 'guardian_sso_connections',
        'sso_identities' => 'guardian_sso_identities',
        'scim_tokens' => 'guardian_scim_tokens',
        'scim_users' => 'guardian_scim_users',
        'scim_groups' => 'guardian_scim_groups',
        'permissions' => 'guardian_permissions',
        'roles' => 'guardian_roles',
        'permission_role' => 'guardian_permission_role',
        'role_assignments' => 'guardian_role_assignments',
        'security_events' => 'guardian_security_events',
        'two_factor_methods' => 'guardian_two_factor_methods',
        'recovery_codes' => 'guardian_recovery_codes',
        'passkeys' => 'guardian_passkeys',
        'one_time_codes' => 'guardian_one_time_codes',
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Server
    |--------------------------------------------------------------------------
    |
    | Exposes the SSO and SCIM management actions as MCP tools. Both
    | transports are disabled by default.
    |
    | web:   An HTTP MCP endpoint at `route`, wrapped in `middleware`.
    |        SECURITY: tools check the caller's permissions, so add
    |        authentication middleware (e.g. `auth:sanctum`) before enabling.
    | local: A stdio server started with `php artisan mcp:start <handle>`.
    |
    */

    'mcp' => [
        'web' => [
            'enabled' => false,
            'route' => 'mcp/guardian',
            'middleware' => ['api'],
        ],
        'local' => [
            'enabled' => false,
            'handle' => 'guardian',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cortex
    |--------------------------------------------------------------------------
    |
    | With refactor-circus/cortex installed, Cortex agents can manage SSO
    | connections and SCIM tokens, acting as the signed-in user. `tools`
    | limits which tools are offered (null offers all).
    |
    */

    'cortex' => [
        'enabled' => true,
        'server' => 'guardian',
        'tools' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Migrations
    |--------------------------------------------------------------------------
    |
    | Rolling back Guardian's create migrations refuses to drop a table that
    | holds rows, and names the tables: after an upgrade from Roster they
    | hold your connections, identities and tokens.
    |
    | force_rollback: Drop them anyway. SECURITY: this deletes every SSO
    |                 connection, linked identity and SCIM token. Set
    |                 GUARDIAN_FORCE_ROLLBACK=true only for the rollback you
    |                 mean, never in a routine deploy.
    |
    */

    'migrations' => [
        'force_rollback' => (bool) env('GUARDIAN_FORCE_ROLLBACK', false),
    ],

];
