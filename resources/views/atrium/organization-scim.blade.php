{{--
    The `guardian.scim` tab: a new token's one-time alert, the SCIM base URL,
    the organization's tokens and the form for a new one. Data: `tokens`
    (25 a page), `baseUrl`, `organization` and `connections` (the form's
    SSO connection options).
--}}
@php($token = session(\RefactorCircus\Guardian\Atrium\Http\Controllers\ScimUiController::TOKEN))

<div class="flex flex-col gap-4">
    @if ($token)
        <x-atrium::alert variant="warning" :title="__('guardian::guardian.scim_token_created')">
            <code class="break-all font-mono text-sm" data-testid="scim-token">{{ $token }}</code>
        </x-atrium::alert>
    @endif

    <x-atrium::card :title="__('guardian::guardian.scim_base_url')">
        <code class="break-all font-mono text-sm" data-testid="scim-base-url">{{ $baseUrl }}</code>
    </x-atrium::card>

    @if ($tokens->isEmpty())
        <x-atrium::empty-state :title="__('guardian::guardian.no_scim_tokens')" />
    @else
        <x-atrium::table striped>
            <x-slot:head>
                <x-atrium::table.row>
                    <x-atrium::table.cell heading>{{ __('guardian::guardian.name') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading>{{ __('guardian::guardian.last_login') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading>{{ __('guardian::guardian.expires') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading class="text-right">{{ __('guardian::guardian.actions') }}</x-atrium::table.cell>
                </x-atrium::table.row>
            </x-slot:head>
            @foreach ($tokens as $scimToken)
                <x-atrium::table.row data-testid="scim-token-row">
                    <x-atrium::table.cell>
                        {{ $scimToken->name }}
                        @if ($scimToken->revoked_at)
                            <x-atrium::badge>{{ __('guardian::guardian.revoked') }}</x-atrium::badge>
                        @endif
                    </x-atrium::table.cell>
                    <x-atrium::table.cell>{{ $scimToken->last_used_at?->diffForHumans() ?? __('guardian::guardian.never') }}</x-atrium::table.cell>
                    <x-atrium::table.cell>{{ $scimToken->expires_at?->toFormattedDateString() ?? __('guardian::guardian.never') }}</x-atrium::table.cell>
                    <x-atrium::table.cell>
                        @unless ($scimToken->revoked_at)
                            <form method="POST" action="{{ route('atrium.guardian.scim-tokens.revoke', $scimToken->id) }}" class="flex justify-end">
                                @csrf
                                @method('DELETE')
                                <x-atrium::icon-button icon="no-symbol" :label="__('guardian::guardian.revoke')" type="submit" size="sm" variant="danger" />
                            </form>
                        @endunless
                    </x-atrium::table.cell>
                </x-atrium::table.row>
            @endforeach
        </x-atrium::table>
        <x-atrium::pagination :paginator="$tokens" />
    @endif

    <x-atrium::card :title="__('guardian::guardian.new_scim_token')">
        <form method="POST" action="{{ route('atrium.guardian.scim-tokens.store', $organization) }}" class="flex flex-wrap items-start gap-3">
            @csrf
            <x-atrium::form.input name="name" id="scim-token-name" :label="__('guardian::guardian.name')" wrapper="w-64" required />
            <x-atrium::form.input name="expires_in_days" type="number" :label="__('guardian::guardian.expires_in_days')" wrapper="w-40" />
            <x-atrium::form.select
                name="sso_connection"
                :label="__('guardian::guardian.link_sso')"
                :placeholder="__('guardian::guardian.none')"
                :options="$connections->mapWithKeys(fn ($c) => [$c->slug => $c->name])"
                wrapper="w-56" />
            <x-atrium::form.actions>
                <x-atrium::icon-button icon="plus" :label="__('guardian::guardian.create')" variant="primary" type="submit" data-testid="create-scim-token" />
            </x-atrium::form.actions>
        </form>
    </x-atrium::card>
</div>
