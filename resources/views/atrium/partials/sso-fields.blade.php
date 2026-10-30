{{--
    Protocol settings. With a fixed $protocol (editing) only its fields
    render; without one (creating) every field renders and Alpine shows the
    ones for the protocol picked in the form's `protocol` select. The Action
    checks the right ones are present either way.
--}}
@php
    $fields = [
        'issuer' => ['protocols' => ['oidc'], 'label' => 'Issuer', 'hint' => null],
        'tenant' => ['protocols' => ['azure'], 'label' => 'Tenant', 'hint' => __('guardian::guardian.tenant_hint')],
        'client_id' => ['protocols' => ['oidc', 'azure'], 'label' => 'Client ID', 'hint' => null],
        'client_secret' => ['protocols' => ['oidc', 'azure'], 'label' => 'Client secret', 'hint' => $editing ? __('guardian::guardian.client_secret_hint') : null],
        'metadata_url' => ['protocols' => ['saml'], 'label' => 'Metadata URL', 'hint' => null],
        'entity_id' => ['protocols' => ['saml'], 'label' => 'Entity ID', 'hint' => null],
        'sso_url' => ['protocols' => ['saml'], 'label' => 'SSO URL', 'hint' => null],
        'certificate' => ['protocols' => ['saml'], 'label' => 'Certificate', 'hint' => null],
    ];
    $protocol ??= null;
@endphp

<div class="grid gap-3 sm:grid-cols-2">
    @foreach ($fields as $name => $field)
        @continue($protocol !== null && ! in_array($protocol, $field['protocols'], true))

        <div
            @if ($protocol === null)
                x-show="@js($field['protocols']).includes(protocol)" x-cloak
            @endif
            data-sso-field="{{ $name }}"
            @class(['sm:col-span-2' => $name === 'certificate'])>
            @if ($name === 'certificate')
                <x-atrium::form.textarea :name="$name" :label="$field['label']" rows="3" />
            @else
                <x-atrium::form.input
                    :name="$name"
                    :type="$name === 'client_secret' ? 'password' : 'text'"
                    :label="$field['label']"
                    :value="$name === 'client_secret' ? null : old($name, $settings[$name] ?? null)"
                    :hint="$field['hint']" />
            @endif
        </div>
    @endforeach
</div>
<div class="flex flex-wrap gap-4">
    <x-atrium::form.checkbox name="jit" value="1" :checked="$jit ?? true" :label="__('guardian::guardian.jit')" />
    <x-atrium::form.checkbox name="enforced" value="1" :checked="$enforced ?? false" :label="__('guardian::guardian.enforced')" />
    <x-atrium::form.checkbox name="enabled" value="1" :checked="$enabled ?? true" :label="__('guardian::guardian.enabled')" />
</div>
