<?php

declare(strict_types=1);

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
 * Guardian works on refactor-circus/roster's users and organizations. Without
 * Roster installed it registers nothing, and `php artisan about` says so.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On
    |--------------------------------------------------------------------------
    |
    | Organizations can sign their people in through their own identity
    | provider (OIDC, SAML or Microsoft Entra ID), configured per
    | organization in Atrium or via the API. Requires the optional package
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
    | Each request needs one of the organization's SCIM tokens (issued in
    | Atrium or via the API, shown once, revocable). SECURITY: a token can
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
    | SECURITY: each route checks the caller's Roster permissions, but it
    | needs an authenticated user to check: add authentication middleware
    | (e.g. `auth:sanctum`) before enabling it.
    |
    */

    'routes' => [
        'enabled' => false,
        'prefix' => 'guardian',
        'middleware' => ['api', 'throttle:guardian'],
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
    | Policies
    |--------------------------------------------------------------------------
    |
    | The policy the Gate uses for each Guardian model. Point a model at a
    | class of your own (extending the bundled one, or not) to change who may
    | do what. The bundled policies answer with Roster's permissions:
    | `guardian.sso.view`, `guardian.sso.manage` and `guardian.scim.manage`,
    | in the record's organization.
    |
    */

    'policies' => [
        SsoConnectionModel::class => SsoConnectionPolicy::class,
        SsoIdentityModel::class => SsoIdentityPolicy::class,
        ScimTokenModel::class => ScimTokenPolicy::class,
        ScimUserModel::class => ScimUserPolicy::class,
        ScimGroupModel::class => ScimGroupPolicy::class,
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
    |        SECURITY: tools check the caller's Roster permissions, so add
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

    /*
    |--------------------------------------------------------------------------
    | Atrium
    |--------------------------------------------------------------------------
    |
    | With refactor-circus/atrium installed, Guardian adds its tabs and
    | sections to other packages' Atrium screens.
    |
    | screens:            Screen key => what Guardian adds there. A plain
    |                     value is a tab (`guardian.sso`, `guardian.scim`);
    |                     a `slot => key` entry is a section in that slot
    |                     (`guardian.sso-identities`). Move an entry to
    |                     another screen, or empty the list to add nothing.
    |                     Each tab and section still checks its permission.
    | organization_route: The route the SSO connection page links back to,
    |                     with the organization and `tab=guardian.sso`. No
    |                     link shows when the route does not exist.
    |
    | A bad entry (an unknown key, or one Atrium rejects) throws an
    | InvalidArgumentException at boot in the local and testing
    | environments. Elsewhere it is reported once and skipped, and the rest
    | registers.
    |
    */

    'atrium' => [
        'screens' => [
            'roster.organization' => ['guardian.sso', 'guardian.scim'],
            'roster.user' => ['aside' => 'guardian.sso-identities'],
        ],
        'organization_route' => 'atrium.roster.organizations.show',
    ],

];
