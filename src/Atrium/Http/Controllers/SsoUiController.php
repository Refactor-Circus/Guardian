<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Atrium;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Guardian\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Guardian\Atrium\Screens;
use RefactorCircus\Guardian\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Actions\DeleteSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Actions\ShowSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Actions\UnlinkSsoIdentityAction;
use RefactorCircus\Guardian\Domains\Sso\Actions\UpdateSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Guardian\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;

/**
 * Atrium: an organization's SSO connections, the connection page
 * (`guardian.sso-connection`), and unlinking identities.
 */
final class SsoUiController
{
    use AuthorizesScreens;

    public const string SCREEN = 'guardian.sso-connection';

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen($request, 'guardian.sso.manage', $model);
        $this->booleans($request);

        $connection = app(CreateSsoConnectionAction::class)->execute($model, CreateSsoConnectionData::from($request->all()));

        return redirect()
            ->route('atrium.guardian.sso.show', $connection->slug)
            ->with('status', __('guardian::guardian.sso_connection_created'));
    }

    /**
     * The connection page, declared to Atrium's registry as a detail screen
     * with the connection as its record. Extensions' permissions are asked
     * through Roster's Authorizer in the connection's organization.
     */
    public function show(Request $request, string $connection): View
    {
        $model = $this->find($connection);
        $organization = $model->organization;
        // Before anything resolves: the Action, and every tab and section.
        $this->authorizeScreen($request, 'guardian.sso.view', $organization);

        $model = app(ShowSsoConnectionAction::class)->execute($model);

        $screen = Screen::standard(self::SCREEN, Screen::DETAIL)
            ->record($model)
            ->access(fn (string $permission, Request $request, ?Model $record): bool => app(Authorizer::class)->check($this->actor($request), $permission, $organization));

        /** @var view-string $view */
        $view = 'guardian::atrium.sso-connection';

        return view($view, [
            'connection' => $model,
            'resolved' => app(Atrium::class)->screens()->resolve($screen, $request),
            'back' => $this->backLink($organization),
        ]);
    }

    public function update(Request $request, string $connection): RedirectResponse
    {
        $model = $this->find($connection);
        $this->authorizeScreen($request, 'guardian.sso.manage', $model->organization);
        $this->booleans($request);

        $model = app(UpdateSsoConnectionAction::class)->execute($model, UpdateSsoConnectionData::from($request->all(), $model));

        return redirect()
            ->route('atrium.guardian.sso.show', $model->slug)
            ->with('status', __('guardian::guardian.sso_connection_updated'));
    }

    public function destroy(Request $request, string $connection): RedirectResponse
    {
        $model = $this->find($connection);
        $organization = $model->organization;
        $this->authorizeScreen($request, 'guardian.sso.manage', $organization);

        app(DeleteSsoConnectionAction::class)->execute($model);

        return $this->toOrganization($organization, Screens::SSO, 'guardian::guardian.sso_connection_deleted');
    }

    public function unlink(Request $request, string $identity): RedirectResponse
    {
        $model = SsoIdentityModel::query()->whereKey($identity)->firstOrFail();
        $actor = $this->actor($request);
        $this->authorizeScreen($request, 'guardian.sso.manage', $model->connection?->organization, $model->ownedBy($actor) ? $actor : null);

        app(UnlinkSsoIdentityAction::class)->execute($model);

        return redirect()->back()->with('status', __('guardian::guardian.sso_identity_unlinked'));
    }

    private function find(string $slug): SsoConnectionModel
    {
        return SsoConnectionModel::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * The organization's SSO tab (`guardian.atrium.organization_route`), or
     * null when that route does not exist.
     */
    private function backLink(?OrganizationModel $organization): ?string
    {
        $route = config('guardian.atrium.organization_route');

        return is_string($route) && $organization !== null && Route::has($route)
            ? route($route, [$organization, 'tab' => Screens::SSO])
            : null;
    }

    /**
     * Unchecked boxes send nothing; treat them as false.
     */
    private function booleans(Request $request): void
    {
        foreach (['jit', 'enforced', 'enabled'] as $field) {
            $request->merge([$field => $request->boolean($field)]);
        }
    }
}
