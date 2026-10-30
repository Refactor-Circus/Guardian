<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Services;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\SocialiteServiceProvider;
use RefactorCircus\Guardian\Domains\Sso\Data\IdentityClaims;
use RefactorCircus\Guardian\Domains\Sso\Exceptions\SsoUnavailableException;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use SocialiteProviders\Azure\Provider as AzureProvider;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Saml2\Provider as Saml2Provider;

/**
 * Builds the Socialite driver for a connection (OIDC, SAML or Microsoft
 * Entra ID), from its own settings.
 *
 * The protocol packages are optional: nothing here touches them unless they
 * are installed.
 */
class Sso
{
    public function __construct(private readonly Request $request) {}

    /**
     * Whether any single sign-on protocol can be used.
     */
    public function available(?string $protocol = null): bool
    {
        $socialite = class_exists(SocialiteServiceProvider::class);

        return match ($protocol) {
            SsoConnectionModel::OIDC => $socialite && class_exists(JWT::class),
            SsoConnectionModel::SAML => $socialite && class_exists(Saml2Provider::class),
            SsoConnectionModel::AZURE => $socialite && class_exists(AzureProvider::class),
            default => $socialite && (class_exists(JWT::class) || class_exists(Saml2Provider::class) || class_exists(AzureProvider::class)),
        };
    }

    public function provider(SsoConnectionModel $connection): Provider
    {
        if (! $this->available($connection->protocol)) {
            throw SsoUnavailableException::forProtocol($connection->protocol);
        }

        $callback = route('guardian.sso.callback', $connection->slug);

        if ($connection->protocol === SsoConnectionModel::OIDC) {
            return new OidcProvider(
                $this->request,
                (string) $connection->setting('issuer'),
                (string) $connection->setting('client_id'),
                (string) $connection->setting('client_secret'),
                $callback,
            );
        }

        if ($connection->protocol === SsoConnectionModel::AZURE) {
            $clientId = (string) $connection->setting('client_id');
            $secret = (string) $connection->setting('client_secret');

            $provider = (new AzureProvider($this->request, $clientId, $secret, $callback))
                ->setConfig(new Config($clientId, $secret, $callback, ['tenant' => (string) $connection->setting('tenant')]));

            // Tests (and apps needing a proxy) can supply the HTTP client.
            if (app()->bound('guardian.sso.http')) {
                $provider->setHttpClient(app('guardian.sso.http'));
            }

            return $provider;
        }

        return (new Saml2Provider($this->request))->setConfig(new Config('', '', $callback, $this->samlConfig($connection)));
    }

    /**
     * Map a Socialite user from any protocol to the claims Guardian trusts.
     */
    public function claims(SsoConnectionModel $connection, User $user): IdentityClaims
    {
        $raw = method_exists($user, 'getRaw') ? (array) $user->getRaw() : [];

        $email = match ($connection->protocol) {
            // Never Entra's `mail`: tenant admins can set it to anything.
            // A userPrincipalName must sit on a domain the tenant verified.
            SsoConnectionModel::AZURE => is_string($raw['userPrincipalName'] ?? null) ? $raw['userPrincipalName'] : null,
            default => $user->getEmail(),
        };

        return new IdentityClaims(
            subject: (string) $user->getId(),
            email: $email === null ? null : strtolower($email),
            name: $user->getName(),
            raw: $raw,
            // An OIDC provider may hand back an address it never checked:
            // only a literal `true` lets it link or create an account (D-039).
            emailVerified: $connection->protocol !== SsoConnectionModel::OIDC || ($raw['email_verified'] ?? null) === true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function samlConfig(SsoConnectionModel $connection): array
    {
        $idp = $connection->setting('metadata_url') !== null
            ? ['metadata' => $connection->setting('metadata_url')]
            : [
                'entityid' => $connection->setting('entity_id'),
                'acs' => $connection->setting('sso_url'),
                'certificate' => $connection->setting('certificate'),
            ];

        return $idp + [
            'sp_entityid' => route('guardian.sso.metadata', $connection->slug),
            'sp_acs' => ltrim((string) parse_url(route('guardian.sso.callback', $connection->slug), PHP_URL_PATH), '/'),
            'sp_default_binding_method' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ];
    }
}
