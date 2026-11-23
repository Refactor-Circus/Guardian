<?php

declare(strict_types=1);

/*
 * The Roster classes Guardian may name (criterion 4, D-003): the ones SSO and
 * SCIM named before v2, minus Roster's Role and Permission domains, which
 * v2 replaces with Keystone's authorization contract. The list only shrinks:
 * a new name here widens the existing cross-package reference, which needs
 * its own spec. Roster's old SSO and SCIM model names are stored identifiers
 * (morph aliases, the handover check), named as strings.
 */

return [
    'RefactorCircus\\Roster\\Contracts\\SignInRequirement',
    'RefactorCircus\\Roster\\Data\\ExternalSignIn',
    'RefactorCircus\\Roster\\Domains\\Organization\\Actions\\RemoveMemberAction',
    'RefactorCircus\\Roster\\Domains\\Organization\\Enums\\MembershipSource',
    'RefactorCircus\\Roster\\Domains\\Organization\\Models\\MembershipModel',
    'RefactorCircus\\Roster\\Domains\\Organization\\Models\\OrganizationModel',
    'RefactorCircus\\Roster\\Domains\\Organization\\Services\\MembershipSources',
    'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimGroupModel',
    'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimTokenModel',
    'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimUserModel',
    'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoConnectionModel',
    'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoIdentityModel',
    'RefactorCircus\\Roster\\Domains\\Team\\Actions\\AddTeamMemberAction',
    'RefactorCircus\\Roster\\Domains\\Team\\Actions\\CreateTeamAction',
    'RefactorCircus\\Roster\\Domains\\Team\\Actions\\DeleteTeamAction',
    'RefactorCircus\\Roster\\Domains\\Team\\Actions\\RemoveTeamMemberAction',
    'RefactorCircus\\Roster\\Domains\\Team\\Actions\\UpdateTeamAction',
    'RefactorCircus\\Roster\\Domains\\Team\\Data\\AddTeamMemberData',
    'RefactorCircus\\Roster\\Domains\\Team\\Data\\CreateTeamData',
    'RefactorCircus\\Roster\\Domains\\Team\\Data\\UpdateTeamData',
    'RefactorCircus\\Roster\\Domains\\Team\\Models\\TeamMemberModel',
    'RefactorCircus\\Roster\\Domains\\Team\\Models\\TeamModel',
    'RefactorCircus\\Roster\\Domains\\User\\Actions\\CreateUserAction',
    'RefactorCircus\\Roster\\Domains\\User\\Actions\\DeactivateUserAction',
    'RefactorCircus\\Roster\\Domains\\User\\Actions\\ReactivateUserAction',
    'RefactorCircus\\Roster\\Domains\\User\\Actions\\UpdateProfileAction',
    'RefactorCircus\\Roster\\Domains\\User\\Actions\\UpdateUserAction',
    'RefactorCircus\\Roster\\Domains\\User\\Data\\CreateUserData',
    'RefactorCircus\\Roster\\Domains\\User\\Data\\DeactivateUserData',
    'RefactorCircus\\Roster\\Domains\\User\\Data\\UpdateProfileData',
    'RefactorCircus\\Roster\\Domains\\User\\Data\\UpdateUserData',
    'RefactorCircus\\Roster\\Roster',
    'RefactorCircus\\Roster\\Support\\Statuses',
    'RefactorCircus\\Roster\\Support\\Tables',
    'RefactorCircus\\Roster\\Support\\UserKey',
    'RefactorCircus\\Roster\\Support\\Users',
];
