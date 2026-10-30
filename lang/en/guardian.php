<?php

declare(strict_types=1);

return [

    'label' => 'Guardian',

    // Shared by the Atrium screens
    'name' => 'Name',
    'create' => 'Create',
    'save' => 'Save',
    'delete' => 'Delete',
    'none' => '—',
    'settings' => 'Settings',
    'actions' => 'Actions',
    'revoke' => 'Revoke',
    'expires' => 'Expires',

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
    'sso_failed_unverified_email' => 'Your identity provider has not verified this email address. Verify it there, or sign in another way and link it from your account.',
    'sso_failed_no_account' => 'There is no account for this email yet. Ask for an invitation.',
    'sso_failed_invalid_response' => 'The identity provider\'s response could not be verified.',
    'sso_failed_pending' => 'Your account is awaiting approval. You will be able to sign in once it is approved.',
    'no_sso_connections' => 'No single sign-on connections yet.',
    'new_sso_connection' => 'New SSO connection',
    'protocol' => 'Protocol',
    'protocol_oidc' => 'OpenID Connect',
    'protocol_saml' => 'SAML 2.0',
    'protocol_azure' => 'Microsoft Entra ID',
    'jit' => 'Create accounts on first sign-in',
    'enforced' => 'Require SSO for this organization\'s domains',
    'enabled' => 'Enabled',
    'callback_url' => 'Callback / ACS URL',
    'metadata_url' => 'Service provider metadata',
    'test_sign_in' => 'Test sign-in',
    'unlink' => 'Unlink',
    'last_login' => 'Last sign-in',
    'sso_connection_created' => 'SSO connection created.',
    'sso_connection_updated' => 'SSO connection updated.',
    'sso_connection_deleted' => 'SSO connection deleted.',
    'sso_identity_unlinked' => 'Identity unlinked.',
    'client_secret_hint' => 'Leave blank to keep the current secret.',
    'tenant_hint' => 'Your Entra tenant ID or verified domain. Multi-tenant values (common, organizations) are not allowed.',

    // Organizations, as the API and MCP tools name them
    'unknown_organization' => 'No organization has this slug.',

    // SCIM provisioning
    'scim' => 'SCIM provisioning',
    'scim_tokens' => 'SCIM tokens',
    'scim_deprovisioned' => 'Deprovisioned from :organization by its identity provider.',
    'unknown_sso_connection' => 'No SSO connection in this organization has this slug.',
    'scim_base_url' => 'SCIM base URL',
    'no_scim_tokens' => 'No SCIM tokens yet.',
    'new_scim_token' => 'New SCIM token',
    'scim_token_created' => 'Copy this token now. It will not be shown again.',
    'scim_token_revoked' => 'SCIM token revoked.',
    'expires_in_days' => 'Expires in (days)',
    'never' => 'Never',
    'revoked' => 'Revoked',
    'link_sso' => 'Link to SSO connection',

];
