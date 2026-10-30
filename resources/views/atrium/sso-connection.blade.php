{{--
    The `guardian.sso-connection` screen: one SSO connection. It has only the
    synthetic `overview` tab and the standard detail slots. An open
    registered tab's panel replaces the page; `header.actions` sits in the
    page header, `before` above the page, `aside` beside it while it has
    sections to show, and `after` below it.
--}}
@php
    $resource = (new \RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource($connection))->resolve();
    $manage = app(\RefactorCircus\Roster\Domains\Permission\Services\Authorizer::class)->check(auth()->user(), 'guardian.sso.manage', $connection->organization);
    $aside = $resolved->openRegisteredTab === null && $resolved->sections('aside') !== [];
@endphp

<x-atrium::layout :title="$connection->name">
    <x-atrium::page-header :title="$connection->name" :description="__('guardian::guardian.protocol_'.$connection->protocol).' · '.$connection->organization?->name">
        <x-slot:actions>
            <div class="flex items-center gap-2" data-testid="slot-header.actions">
                @if ($back !== null)
                    <x-atrium::icon-button icon="arrow-left" :label="$connection->organization?->name" variant="ghost" :href="$back" data-testid="back-to-organization" />
                @endif
                <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
            </div>
        </x-slot:actions>
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    <div class="mt-5 flex flex-col gap-4">
        <x-atrium::flash />

        @if ($resolved->openRegisteredTab !== null)
            <x-atrium::screen-tab-panel :screen="$resolved" />
        @else
            @if ($resolved->sections('before') !== [])
                <div class="flex flex-col gap-4" data-testid="slot-before">
                    <x-atrium::screen-sections :screen="$resolved" slot="before" />
                </div>
            @endif

            @if ($aside)
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="flex flex-col gap-4 lg:col-span-2">
            @else
                <div class="flex flex-col gap-4">
            @endif

            <x-atrium::card :title="__('guardian::guardian.sso')">
                <x-atrium::description-list>
                    <x-atrium::description-list.item :term="__('guardian::guardian.callback_url')" class="break-all font-mono text-xs" data-testid="callback-url">{{ $resource['callback_url'] ?? __('guardian::guardian.none') }}</x-atrium::description-list.item>
                    @if ($resource['metadata_url'])
                        <x-atrium::description-list.item :term="__('guardian::guardian.metadata_url')" class="break-all font-mono text-xs">{{ $resource['metadata_url'] }}</x-atrium::description-list.item>
                    @endif
                    <x-atrium::description-list.item :term="__('guardian::guardian.sso_identities')">{{ $connection->identities_count }}</x-atrium::description-list.item>
                </x-atrium::description-list>
                @if ($resource['sign_in_url'])
                    <x-atrium::icon-button icon="arrow-top-right-on-square" :label="__('guardian::guardian.test_sign_in')" class="mt-3" variant="ghost" :href="$resource['sign_in_url']" />
                @endif
            </x-atrium::card>

            @if ($manage)
                <x-atrium::card :title="__('guardian::guardian.settings')" data-testid="sso-settings">
                    <form method="POST" action="{{ route('atrium.guardian.sso.update', $connection->slug) }}" class="flex max-w-3xl flex-col gap-4">
                        @csrf
                        @method('PATCH')
                        <x-atrium::form.input name="name" :label="__('guardian::guardian.name')" :value="old('name', $connection->name)" required />
                        @include('guardian::atrium.partials.sso-fields', ['settings' => (array) $resource['settings'], 'editing' => true, 'protocol' => $connection->protocol, 'jit' => $connection->jit, 'enforced' => $connection->enforced, 'enabled' => $connection->enabled])
                        <div>
                            <x-atrium::icon-button icon="check" :label="__('guardian::guardian.save')" variant="primary" type="submit" data-testid="save-sso" />
                        </div>
                    </form>
                </x-atrium::card>

                <form method="POST" action="{{ route('atrium.guardian.sso.destroy', $connection->slug) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('guardian::guardian.delete')" type="submit" variant="danger" data-testid="delete-sso" />
                </form>
            @endif

            @if ($aside)
                    </div>
                    <aside class="flex flex-col gap-4" data-testid="slot-aside">
                        <x-atrium::screen-sections :screen="$resolved" slot="aside" />
                    </aside>
                </div>
            @else
                </div>
            @endif

            @if ($resolved->sections('after') !== [])
                <div class="flex flex-col gap-4" data-testid="slot-after">
                    <x-atrium::screen-sections :screen="$resolved" slot="after" />
                </div>
            @endif
        @endif
    </div>
</x-atrium::layout>
