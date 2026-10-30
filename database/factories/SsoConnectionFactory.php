<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

/**
 * @extends Factory<SsoConnectionModel>
 */
final class SsoConnectionFactory extends Factory
{
    protected $model = SsoConnectionModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Okta',
            'slug' => 'okta-'.Str::lower(Str::random(6)),
            'protocol' => SsoConnectionModel::OIDC,
            'config' => ['issuer' => 'https://idp.example.com', 'client_id' => 'roster-app', 'client_secret' => 'top-secret'],
        ];
    }

    public function azure(): self
    {
        return $this->state([
            'protocol' => SsoConnectionModel::AZURE,
            'config' => ['tenant' => '11111111-2222-3333-4444-555555555555', 'client_id' => 'roster-app', 'client_secret' => 'top-secret'],
        ]);
    }

    public function saml(): self
    {
        return $this->state([
            'protocol' => SsoConnectionModel::SAML,
            'config' => ['entity_id' => 'https://idp.example.com/saml', 'sso_url' => 'https://idp.example.com/saml/sso', 'certificate' => 'MIIC...'],
        ]);
    }
}
