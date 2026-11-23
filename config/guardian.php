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
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoIdentityPolicy;

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
