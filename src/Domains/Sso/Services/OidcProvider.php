<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User;
use RuntimeException;
use Throwable;

/**
 * A generic OpenID Connect driver for Socialite.
 *
 * Endpoints come from the issuer's discovery document. Identity comes only
 * from the id_token, whose signature is checked against the issuer's
 * published keys and whose issuer, audience, expiry and nonce must match.
 */
final class OidcProvider extends AbstractProvider
{
    private const string NONCE_KEY = 'guardian.sso.nonce';

    /**
     * @var array<int, string>
     */
    protected $scopes = ['openid', 'email', 'profile'];

    protected $scopeSeparator = ' ';

    public function __construct(
        Request $request,
        private readonly string $issuer,
        string $clientId,
        string $clientSecret,
        string $redirectUrl,
    ) {
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function discovery(): array
    {
        $issuer = rtrim($this->issuer, '/');

        /** @var array<string, mixed> */
        return Cache::remember(
            'guardian.sso.discovery.'.hash('sha256', $issuer),
            (int) config('guardian.sso.discovery_cache_seconds', 3600),
            fn (): array => (array) Http::acceptJson()->get($issuer.'/.well-known/openid-configuration')->throw()->json(),
        );
    }

    public function user(): User
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());
        $idToken = $response['id_token'] ?? null;

        if (! is_string($idToken)) {
            throw new RuntimeException('The identity provider returned no id_token.');
        }

        $claims = $this->verify($idToken);

        return (new User)->setRaw($claims)->map([
            'id' => (string) $claims['sub'],
            'email' => is_string($claims['email'] ?? null) ? $claims['email'] : null,
            'name' => is_string($claims['name'] ?? null) ? $claims['name'] : null,
        ])->setToken(is_string($response['access_token'] ?? null) ? $response['access_token'] : '');
    }

    /**
     * Verify an id_token and return its claims.
     *
     * @return array<string, mixed>
     */
    public function verify(string $idToken): array
    {
        $discovery = $this->discovery();
        $nonce = $this->request->session()->pull(self::NONCE_KEY);

        try {
            JWT::$leeway = 60;
            $claims = (array) JWT::decode($idToken, JWK::parseKeySet($this->jwks((string) $discovery['jwks_uri']), 'RS256'));
        } catch (Throwable $exception) {
            throw new RuntimeException('The id_token could not be verified: '.$exception->getMessage(), 0, $exception);
        }

        $audience = (array) ($claims['aud'] ?? []);

        if (($claims['iss'] ?? null) !== ($discovery['issuer'] ?? null)) {
            throw new RuntimeException('The id_token was issued by an unexpected issuer.');
        }

        if (! in_array($this->clientId, $audience, true)) {
            throw new RuntimeException('The id_token is not meant for this application.');
        }

        if (! is_string($nonce) || ! is_string($claims['nonce'] ?? null) || ! hash_equals($nonce, $claims['nonce'])) {
            throw new RuntimeException('The id_token nonce does not match this sign-in.');
        }

        if (! is_scalar($claims['sub'] ?? null)) {
            throw new RuntimeException('The id_token has no subject.');
        }

        return $claims;
    }

    protected function getAuthUrl($state): string
    {
        $nonce = Str::random(40);
        $this->request->session()->put(self::NONCE_KEY, $nonce);
        $this->with(['nonce' => $nonce]);

        return $this->buildAuthUrlFromBase((string) $this->discovery()['authorization_endpoint'], $state);
    }

    protected function getTokenUrl(): string
    {
        return (string) $this->discovery()['token_endpoint'];
    }

    /**
     * Exchange the code through Laravel's HTTP client.
     *
     * @return array<string, mixed>
     */
    public function getAccessTokenResponse($code): array
    {
        /** @var array<string, mixed> */
        return (array) Http::asForm()->acceptJson()->post($this->getTokenUrl(), $this->getTokenFields($code))->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function jwks(string $uri): array
    {
        /** @var array<string, mixed> */
        return Cache::remember(
            'guardian.sso.jwks.'.hash('sha256', $uri),
            (int) config('guardian.sso.discovery_cache_seconds', 3600),
            fn (): array => (array) Http::acceptJson()->get($uri)->throw()->json(),
        );
    }
}
