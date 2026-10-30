<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

/**
 * A fake OpenID provider: discovery, JWKS and a token endpoint that hands
 * back whatever id_token the test sets in $idp->idToken.
 */
final class FakeIdp
{
    public string $idToken = '';

    public string $privateKey;

    /** @var array<string, string> */
    public array $jwk;

    public function __construct(public string $issuer = 'https://idp.test')
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $this->privateKey = (string) $pem;
        $details = openssl_pkey_get_details($key)['rsa'];

        $b64 = fn (string $bin): string => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
        $this->jwk = ['kty' => 'RSA', 'kid' => 'k1', 'use' => 'sig', 'alg' => 'RS256', 'n' => $b64($details['n']), 'e' => $b64($details['e'])];

        Http::fake([
            $issuer.'/.well-known/openid-configuration' => Http::response([
                'issuer' => $issuer,
                'authorization_endpoint' => $issuer.'/authorize',
                'token_endpoint' => $issuer.'/token',
                'jwks_uri' => $issuer.'/jwks',
            ]),
            $issuer.'/jwks' => Http::response(['keys' => [$this->jwk]]),
            $issuer.'/token' => fn () => Http::response(['access_token' => 'at', 'id_token' => $this->idToken, 'token_type' => 'Bearer']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public function sign(array $claims, ?string $privateKey = null): string
    {
        return JWT::encode($claims + [
            'iss' => $this->issuer,
            'aud' => 'roster-app',
            'iat' => time(),
            'exp' => time() + 300,
        ], $privateKey ?? $this->privateKey, 'RS256', 'k1');
    }
}

function acmeWithSso(array $connection = [], string $protocol = 'oidc'): SsoConnectionModel
{
    /** @var OrganizationModel $acme */
    $acme = organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);

    $factory = SsoConnectionModel::factory();
    $factory = match ($protocol) {
        'azure' => $factory->azure(),
        'saml' => $factory->saml(),
        default => $factory->state(['config' => ['issuer' => 'https://idp.test', 'client_id' => 'roster-app', 'client_secret' => 'top-secret']]),
    };

    return $factory->create(['organization_id' => $acme->id, 'slug' => 'acme-sso'] + $connection);
}

/**
 * @return array<string, string>
 */
function queryOf(string $url): array
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return $query;
}
