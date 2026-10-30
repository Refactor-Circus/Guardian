{{--
    The `guardian.sso` tab: an organization's SSO connections, and the form
    for a new one. Data: `connections` (25 a page) and `organization`.
--}}
@php($manage = app(\RefactorCircus\Roster\Domains\Permission\Services\Authorizer::class)->check(auth()->user(), 'guardian.sso.manage', $organization))

<div class="flex flex-col gap-4">
    @if ($connections->isEmpty())
        <x-atrium::empty-state :title="__('guardian::guardian.no_sso_connections')" />
    @else
        <x-atrium::table striped>
            <x-slot:head>
                <x-atrium::table.row>
                    <x-atrium::table.cell heading>{{ __('guardian::guardian.name') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading>{{ __('guardian::guardian.protocol') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading>{{ __('guardian::guardian.sso_identities') }}</x-atrium::table.cell>
                </x-atrium::table.row>
            </x-slot:head>
            @foreach ($connections as $connection)
                <x-atrium::table.row data-testid="sso-connection">
                    <x-atrium::table.cell>
                        <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.guardian.sso.show', $connection->slug) }}">{{ $connection->name }}</a>
                        @if ($connection->enforced)
                            <x-atrium::badge variant="warning">{{ __('guardian::guardian.enforced') }}</x-atrium::badge>
                        @endif
                        @unless ($connection->enabled)
                            <x-atrium::badge>{{ __('guardian::guardian.none') }}</x-atrium::badge>
                        @endunless
                    </x-atrium::table.cell>
                    <x-atrium::table.cell>{{ __('guardian::guardian.protocol_'.$connection->protocol) }}</x-atrium::table.cell>
                    <x-atrium::table.cell>{{ $connection->identities_count }}</x-atrium::table.cell>
                </x-atrium::table.row>
            @endforeach
        </x-atrium::table>
        <x-atrium::pagination :paginator="$connections" />
    @endif

    @if ($manage)
        <x-atrium::card :title="__('guardian::guardian.new_sso_connection')">
            <form method="POST" action="{{ route('atrium.guardian.sso.store', $organization) }}" class="flex max-w-3xl flex-col gap-4" x-data="{ protocol: @js(old('protocol', 'oidc')) }" x-init="protocol = $el.querySelector('select[name=protocol]').value">
                @csrf
                <div class="flex flex-wrap gap-3">
                    <x-atrium::form.input name="name" :label="__('guardian::guardian.name')" wrapper="w-56" required />
                    <x-atrium::form.select
                        name="protocol"
                        :label="__('guardian::guardian.protocol')"
                        :options="['oidc' => __('guardian::guardian.protocol_oidc'), 'azure' => __('guardian::guardian.protocol_azure'), 'saml' => __('guardian::guardian.protocol_saml')]"
                        :selected="old('protocol', 'oidc')"
                        x-model="protocol"
                        wrapper="w-56" />
                </div>
                @include('guardian::atrium.partials.sso-fields', ['settings' => [], 'editing' => false])
                <div>
                    <x-atrium::icon-button icon="plus" :label="__('guardian::guardian.create')" variant="primary" type="submit" data-testid="create-sso" />
                </div>
            </form>
        </x-atrium::card>
    @endif
</div>
