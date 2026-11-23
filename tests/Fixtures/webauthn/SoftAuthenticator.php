<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures\webauthn;

use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * A software authenticator for the passkey tests: it answers the options
 * Guardian issues the way a browser and a platform authenticator would,
 * with `none` attestation and an ES256 (P-256) key, so the real
 * web-auth/webauthn-lib checks run with no browser and no network.
 */
final class SoftAuthenticator
{
    public readonly string $credentialId;

    private OpenSSLAsymmetricKey $key;

    public function __construct(
        public string $origin = 'http://localhost',
        public int $signCount = 0,
        ?string $credentialId = null,
    ) {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);

        if ($key === false) {
            throw new RuntimeException('Cannot make a P-256 key.');
        }

        $this->key = $key;
        $this->credentialId = $credentialId ?? random_bytes(32);
    }

    /**
     * The credential a browser answers to registration options.
     *
     * @param  array<string, mixed>  $options  the options as Guardian answered them
     * @param  array<string, mixed>  $clientData  fields to override in the client data
     * @return array<string, mixed>
     */
    public function register(array $options, array $clientData = []): array
    {
        $rpId = (string) ($options['rp']['id'] ?? 'localhost');
        $clientDataJson = (string) json_encode(array_merge(['type' => 'webauthn.create', 'challenge' => $options['challenge'], 'origin' => $this->origin, 'crossOrigin' => false], $clientData), JSON_UNESCAPED_SLASHES);

        $authData = hash('sha256', $rpId, true)
            .chr(0x45) // user present, user verified, attested credential data
            .pack('N', $this->signCount)
            .str_repeat("\0", 16) // AAGUID: none
            .pack('n', strlen($this->credentialId))
            .$this->credentialId
            .$this->cosePublicKey();

        $attestationObject = Cbor::encode(['fmt' => 'none', 'attStmt' => [], 'authData' => new CborBytes($authData)]);

        return [
            'id' => self::base64Url($this->credentialId),
            'rawId' => self::base64Url($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::base64Url($clientDataJson),
                'attestationObject' => self::base64Url($attestationObject),
                'transports' => ['internal'],
            ],
        ];
    }

    /**
     * The assertion a browser answers to assertion options, counting one
     * more signature (or the counter given).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function assert(array $options, string $userHandle, ?int $signCount = null): array
    {
        $this->signCount = $signCount ?? $this->signCount + 1;
        $rpId = (string) ($options['rpId'] ?? 'localhost');
        $clientDataJson = (string) json_encode(['type' => 'webauthn.get', 'challenge' => $options['challenge'], 'origin' => $this->origin, 'crossOrigin' => false], JSON_UNESCAPED_SLASHES);

        $authData = hash('sha256', $rpId, true).chr(0x05).pack('N', $this->signCount);

        openssl_sign($authData.hash('sha256', $clientDataJson, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return [
            'id' => self::base64Url($this->credentialId),
            'rawId' => self::base64Url($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::base64Url($clientDataJson),
                'authenticatorData' => self::base64Url($authData),
                'signature' => self::base64Url((string) $signature),
                'userHandle' => self::base64Url($userHandle),
            ],
        ];
    }

    public static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function cosePublicKey(): string
    {
        $details = openssl_pkey_get_details($this->key);
        $ec = is_array($details) ? $details['ec'] : [];

        return Cbor::encode([
            1 => 2, // kty: EC2
            3 => -7, // alg: ES256
            -1 => 1, // crv: P-256
            -2 => new CborBytes(str_pad((string) $ec['x'], 32, "\0", STR_PAD_LEFT)),
            -3 => new CborBytes(str_pad((string) $ec['y'], 32, "\0", STR_PAD_LEFT)),
        ]);
    }
}
