{{--
    The `guardian.sso-identities` section: a user's SSO identities, shown
    only when they have one. Data: `identities` (50 a page) and `record`,
    the user.
--}}
@php
    $authorizer = app(\RefactorCircus\Roster\Domains\Permission\Services\Authorizer::class);
    $viewer = auth()->user();
@endphp

<x-atrium::card :title="__('guardian::guardian.sso_identities')">
    <ul class="flex flex-col gap-1 text-sm">
        @foreach ($identities as $identity)
            <li class="flex items-center justify-between gap-2" data-testid="sso-identity">
                <span>{{ $identity->connection?->name }} — {{ $identity->email ?? $identity->subject }} <span class="opacity-60">{{ __('guardian::guardian.last_login') }}: {{ $identity->last_login_at?->diffForHumans() ?? __('guardian::guardian.none') }}</span></span>
                @if ($authorizer->check($viewer, 'guardian.sso.manage', $identity->connection?->organization, $identity->ownedBy($viewer) ? $viewer : null))
                    <form method="POST" action="{{ route('atrium.guardian.sso-identities.destroy', $identity->id) }}">
                        @csrf
                        @method('DELETE')
                        <x-atrium::icon-button icon="link-slash" :label="__('guardian::guardian.unlink')" type="submit" size="sm" variant="ghost" data-testid="unlink-sso-identity" />
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
</x-atrium::card>
