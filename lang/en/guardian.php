<?php

declare(strict_types=1);

return [

    // Single sign-on
    'sso' => 'Single sign-on',
    'sso_connections' => 'SSO connections',
    'sso_identities' => 'SSO identities',
    'sso_setting_required' => 'The :setting setting is required for this protocol.',
    'sso_https_required' => 'Identity provider URLs must use HTTPS.',
    'sso_not_found' => 'No single sign-on is set up for this email domain.',
    'sso_required' => ':organization requires single sign-on. Sign in at :url',
    'sso_linked' => 'Your single sign-on identity is linked.',
    'sso_identity_taken' => 'That identity is already linked to another account.',
    'sso_failed_disabled' => 'This sign-in method is turned off.',
    'sso_failed_inactive' => 'Your account is not active.',
    'sso_failed_untrusted_email' => 'Your identity provider cannot sign in this email address. Sign in another way and link it from your account.',
    'sso_failed_no_account' => 'There is no account for this email yet. Ask for an invitation.',
    'sso_failed_invalid_response' => 'The identity provider\'s response could not be verified.',
    'sso_failed_pending' => 'Your account is awaiting approval. You will be able to sign in once it is approved.',

    // Organizations, as the API and MCP tools name them
    'unknown_organization' => 'No organization has this slug.',

    // SCIM provisioning
    'scim' => 'SCIM provisioning',
    'scim_tokens' => 'SCIM tokens',
    'scim_deprovisioned' => 'Deprovisioned from :organization by its identity provider.',
    'unknown_sso_connection' => 'No SSO connection in this organization has this slug.',

];
