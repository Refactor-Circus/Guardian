<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Passkeys;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Guardian\Support\Users;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Exception\CounterException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Passkey registration and assertion (criterion 30) through
 * web-auth/webauthn-lib, the only place Guardian uses it (D-010). Options
 * are answered as the JSON a browser's WebAuthn API takes and kept server
 * side by `PasskeyChallenges`; responses are verified against them, with
 * `none` attestation, the relying party id `guardian.two_factor.passkeys.rp_id`
 * (default: the host of `app.url`) and the allowed origins.
 */
final class PasskeyCeremony
{
    public const string REGISTRATION = 'registration';

    public const string ASSERTION = 'assertion';

    /**
     * The longest credential id stored (base64url), so the unique index fits.
     */
    public const int MAX_CREDENTIAL_ID = 512;

    private ?SerializerInterface $serializer = null;

    public function __construct(
        private readonly Repository $config,
        private readonly PasskeyChallenges $challenges,
        private readonly Users $users,
    ) {}

    /**
     * Registration options for a user, kept for five minutes; their
     * existing passkeys are excluded.
     *
     * @param  iterable<PasskeyModel>  $existing
     * @return array<string, mixed>
     */
    public function registrationOptions(Authenticatable $user, iterable $existing): array
    {
        $key = UserKeys::of($user);
        $email = $user instanceof Model ? $this->users->email($user) : null;
        $name = $email ?? (string) $key;

        $options = PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create($this->rpName(), $this->rpId()),
            PublicKeyCredentialUserEntity::create($name, $this->userHandle($user), $name),
            random_bytes(32),
            [PublicKeyCredentialParameters::createPk(-7), PublicKeyCredentialParameters::createPk(-257)],
            AuthenticatorSelectionCriteria::create(userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED, residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            array_map(fn (PasskeyModel $passkey): PublicKeyCredentialDescriptor => $this->descriptor($passkey), [...$existing]),
            PasskeyChallenges::TTL_SECONDS * 1000,
        );

        return $this->keep(self::REGISTRATION, $key, $options);
    }

    /**
     * Verify a registration response against the options kept for the
     * user, once.
     *
     * @param  array<string, mixed>  $credential  the browser's PublicKeyCredential as JSON
     *
     * @throws PasskeyException
     */
    public function register(Authenticatable $user, array $credential): RegisteredCredential
    {
        $options = $this->taken(self::REGISTRATION, $user, PublicKeyCredentialCreationOptions::class);

        try {
            $response = $this->credential($credential)->response;

            if (! $response instanceof AuthenticatorAttestationResponse) {
                throw PasskeyException::failed('credential', __('guardian::guardian.passkey_invalid'));
            }

            $record = AuthenticatorAttestationResponseValidator::create($this->ceremonies()->creationCeremony())->check($response, $options, $this->rpId());
        } catch (PasskeyException $e) {
            throw $e;
        } catch (Throwable) {
            throw PasskeyException::failed('credential', __('guardian::guardian.passkey_invalid'));
        }

        $id = self::base64Url($record->publicKeyCredentialId);

        if (strlen($id) > self::MAX_CREDENTIAL_ID) {
            throw PasskeyException::failed('credential', __('guardian::guardian.passkey_invalid'));
        }

        return new RegisteredCredential(
            credentialId: $id,
            publicKey: base64_encode($record->credentialPublicKey),
            signCount: $record->counter,
            transports: array_values(array_filter($record->transports, is_string(...))),
            aaguid: $record->aaguid->toRfc4122(),
        );
    }

    /**
     * Assertion options for a user's passkeys, kept for five minutes.
     *
     * @param  iterable<PasskeyModel>  $passkeys
     * @return array<string, mixed>
     */
    public function assertionOptions(Authenticatable $user, iterable $passkeys): array
    {
        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $this->rpId(),
            array_map(fn (PasskeyModel $passkey): PublicKeyCredentialDescriptor => $this->descriptor($passkey), [...$passkeys]),
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            PasskeyChallenges::TTL_SECONDS * 1000,
        );

        return $this->keep(self::ASSERTION, UserKeys::of($user), $options);
    }

    /**
     * The credential id an assertion names (base64url), or null.
     *
     * @param  array<string, mixed>  $assertion
     */
    public function credentialIdOf(array $assertion): ?string
    {
        $id = $assertion['rawId'] ?? $assertion['id'] ?? null;

        return is_string($id) && $id !== '' ? rtrim(strtr($id, '+/', '-_'), '=') : null;
    }

    /**
     * Verify an assertion by one of the user's passkeys against the options
     * kept for the user, once; answers the new signature counter.
     *
     * @param  array<string, mixed>  $assertion  the browser's PublicKeyCredential as JSON
     *
     * @throws PasskeyException with `signCountMismatch` when the counter did not move forward
     */
    public function verifyAssertion(Authenticatable $user, PasskeyModel $passkey, array $assertion): int
    {
        $options = $this->taken(self::ASSERTION, $user, PublicKeyCredentialRequestOptions::class);

        try {
            $response = $this->credential($assertion)->response;

            if (! $response instanceof AuthenticatorAssertionResponse) {
                throw PasskeyException::failed('assertion', __('guardian::guardian.passkey_invalid'));
            }

            $record = AuthenticatorAssertionResponseValidator::create($this->ceremonies()->requestCeremony())
                ->check($this->record($user, $passkey), $response, $options, $this->rpId(), $this->userHandle($user));
        } catch (PasskeyException $e) {
            throw $e;
        } catch (CounterException) {
            throw PasskeyException::failed('assertion', __('guardian::guardian.passkey_sign_count'), signCountMismatch: true);
        } catch (Throwable) {
            throw PasskeyException::failed('assertion', __('guardian::guardian.passkey_invalid'));
        }

        return $record->counter;
    }

    /**
     * The relying party id: `guardian.two_factor.passkeys.rp_id`, else the
     * host of `app.url`.
     */
    public function rpId(): string
    {
        $id = $this->config->get('guardian.two_factor.passkeys.rp_id');

        if (is_string($id) && $id !== '') {
            return $id;
        }

        $url = $this->config->get('app.url');
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    public function rpName(): string
    {
        $name = $this->config->get('guardian.two_factor.passkeys.rp_name') ?? $this->config->get('app.name');

        return is_string($name) && $name !== '' ? $name : 'Laravel';
    }

    /**
     * The origins a response may come from: `guardian.two_factor.passkeys.origins`,
     * else the origin of `app.url`.
     *
     * @return list<string>
     */
    public function origins(): array
    {
        $origins = $this->config->get('guardian.two_factor.passkeys.origins');

        if (is_array($origins) && $origins !== []) {
            return array_values(array_filter($origins, is_string(...)));
        }

        $url = $this->config->get('app.url');
        $parts = is_string($url) ? parse_url($url) : false;

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return ['https://'.$this->rpId()];
        }

        return [$parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')];
    }

    /**
     * The opaque user handle a passkey carries: an HMAC of the user's key, so
     * the authenticator learns nothing about the account.
     */
    public function userHandle(Authenticatable $user): string
    {
        $key = $this->config->get('app.key');

        return hash_hmac('sha256', 'passkey-user|'.UserKeys::of($user), is_string($key) ? $key : '', true);
    }

    public static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function fromBase64Url(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function keep(string $ceremony, int|string $user, PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options): array
    {
        $json = $this->serializer()->serialize($options, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        ]);

        $this->challenges->put($ceremony, $user, $json);

        /** @var array<string, mixed> */
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * The options kept for the user's ceremony, taken so they work once.
     *
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     *
     * @throws PasskeyException when none are waiting
     */
    private function taken(string $ceremony, Authenticatable $user, string $class): object
    {
        $json = $this->challenges->pull($ceremony, UserKeys::of($user));
        $field = $ceremony === self::REGISTRATION ? 'credential' : 'assertion';

        if ($json === null) {
            throw PasskeyException::failed($field, __('guardian::guardian.passkey_no_challenge'));
        }

        try {
            $options = $this->serializer()->deserialize($json, $class, 'json');
        } catch (Throwable) {
            throw PasskeyException::failed($field, __('guardian::guardian.passkey_no_challenge'));
        }

        return $options instanceof $class ? $options : throw PasskeyException::failed($field, __('guardian::guardian.passkey_no_challenge'));
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function credential(array $json): PublicKeyCredential
    {
        return $this->serializer()->deserialize(json_encode($json, JSON_THROW_ON_ERROR), PublicKeyCredential::class, 'json');
    }

    private function record(Authenticatable $user, PasskeyModel $passkey): CredentialRecord
    {
        return CredentialRecord::create(
            publicKeyCredentialId: self::fromBase64Url($passkey->credential_id),
            type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            transports: $passkey->transports ?? [],
            attestationType: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString($passkey->aaguid ?? '00000000-0000-0000-0000-000000000000'),
            credentialPublicKey: (string) base64_decode($passkey->public_key, true),
            userHandle: $this->userHandle($user),
            counter: $passkey->sign_count,
        );
    }

    private function descriptor(PasskeyModel $passkey): PublicKeyCredentialDescriptor
    {
        return PublicKeyCredentialDescriptor::create(
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            self::fromBase64Url($passkey->credential_id),
            $passkey->transports ?? [],
        );
    }

    private function ceremonies(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory;
        $factory->setAllowedOrigins($this->origins());

        return $factory;
    }

    private function serializer(): SerializerInterface
    {
        return $this->serializer ??= (new WebauthnSerializerFactory(AttestationStatementSupportManager::create([new NoneAttestationStatementSupport])))->create();
    }
}
