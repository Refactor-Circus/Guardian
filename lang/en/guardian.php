<?php

declare(strict_types=1);

return [

    // Single sign-on
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

    // Organizations and connections, as the API and MCP tools name them
    'unknown_organization' => 'No organization has this slug.',
    'unknown_sso_connection' => 'No SSO connection in this organization has this slug.',

    // Roles and permissions
    'unknown_scope_type' => 'No scope has this type.',
    'unknown_scope' => 'No scope of this type has this id.',
    'unknown_role' => 'No role here has this slug.',
    'cannot_grant' => 'You cannot grant permissions you do not hold here: :permissions.',
    'only_super_admins' => 'Only super-admins may give, take away or change a super role.',
    'global_role_shared' => 'A global role cannot belong to one scope.',
    'role_scope_owner' => 'A scope\'s own role may only be assigned in that scope.',
    'role_scope_unknown' => 'A role\'s scope is global, any, or a scope type.',
    'role_slug_taken' => 'A role with this slug already exists here.',
    'cannot_delete_system' => 'Built-in roles cannot be deleted.',
    'role_not_assignable' => 'This role cannot be assigned here.',
    'role_already_assigned' => 'The user already holds this role here.',
    'unknown_user' => 'No user has this id.',
    'permission_name_taken' => 'A permission with this name already exists.',
    'system_permission' => 'Built-in permissions cannot be renamed or deleted.',

    // Two-factor
    'two_factor_required' => 'Two-factor authentication is required: keep at least one factor.',
    'two_factor_method_unavailable' => 'Codes cannot be sent by :type here.',
    'two_factor_throttled' => 'Too many attempts. Try again in :seconds seconds.',
    'two_factor_nothing_pending' => 'Nothing is waiting to be confirmed. Start the enrolment again.',
    'two_factor_invalid_code' => 'This code is not valid.',
    'two_factor_other_factor_needed' => 'Enter a current code from another of your factors, or a recovery code.',
    'two_factor_challenge_required' => 'Confirm it is you with your second factor first.',
    'two_factor_enrollment_required' => 'Set up two-factor authentication to continue.',
    'two_factor_session_required' => 'The two-factor challenge needs a session: sign in through the web.',
    'two_factor_no_such_method' => 'You have no confirmed method of this kind.',
    'two_factor_not_enabled' => 'Two-factor authentication is not on for this account.',

    'passkeys_unavailable' => 'Passkeys are not available: install web-auth/webauthn-lib to use them.',
    'passkey_invalid' => 'This passkey response could not be verified.',
    'passkey_no_challenge' => 'No passkey request is waiting, or it expired. Start again.',
    'passkey_sign_count' => 'This passkey\'s signature counter did not move forward, so it may have been cloned.',
    'passkey_taken' => 'This passkey is already registered.',
    'one_time_code_subject' => 'Your sign-in code',
    'one_time_code_line' => 'Your sign-in code is :code.',
    'one_time_code_expiry' => 'It expires in :minutes minutes and works once.',
    'one_time_code_ignore' => 'If you did not ask for it, someone may know your password: change it.',

    // Sessions and devices
    'session_revoked' => 'This session was signed out. Sign in again.',
    'new_device_subject' => 'New sign-in to your account',
    'new_device_line' => 'Your account was just signed in to from a new device: :device.',
    'new_device_where' => 'From :ip at :time.',
    'new_device_ignore' => 'If this was you, there is nothing to do. If not, change your password and sign out your other sessions.',
    'unknown_device' => 'an unknown device',

    // Passwords
    'password_recently_used' => 'Choose a password you have not used recently.',
    'password_expired' => 'Your password has expired: change it to continue.',

    // Login protection
    'suspicious_subject' => 'Unusual sign-in to your account',
    'suspicious_line' => 'Your account was just signed in to from a new device: :device.',
    'suspicious_failures' => 'It came after :count failed attempts at your password in the past hour.',
    'suspicious_network' => 'It came from a network your account has not been used from before.',
    'suspicious_act' => 'If this was not you, change your password now and sign out your other sessions.',
    'ip_denied' => 'Access from your network is not allowed.',
    'ip_rule_invalid_cidr' => 'Give an IPv4 or IPv6 address, or a CIDR range such as 203.0.113.0/24.',

    // SCIM provisioning
    'scim_deprovisioned' => 'Deprovisioned from :organization by its identity provider.',

];
