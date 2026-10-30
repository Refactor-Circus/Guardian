<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Atrium\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;

/**
 * The same permission checks Guardian's JSON API and MCP tools make, through
 * Roster's Authorizer, for Guardian's Atrium routes.
 */
trait AuthorizesScreens
{
    private function authorizeScreen(Request $request, string $permission, ?OrganizationModel $scope, ?Model $self = null): void
    {
        abort_unless(app(Authorizer::class)->check($this->actor($request), $permission, $scope, $self), 403);
    }

    /**
     * The signed-in user, for Actions that guard against acting on yourself.
     */
    private function actor(Request $request): ?Model
    {
        $user = $request->user();

        return $user instanceof Model ? $user : null;
    }

    /**
     * The organization a route names, by slug.
     */
    private function organization(string $slug): OrganizationModel
    {
        return OrganizationModel::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Back to a tab of the organization's page
     * (`guardian.atrium.organization_route`), or to the previous page when
     * that route does not exist.
     */
    private function toOrganization(?OrganizationModel $organization, string $tab, string $message): RedirectResponse
    {
        $route = config('guardian.atrium.organization_route');
        $redirect = is_string($route) && $organization !== null && Route::has($route)
            ? redirect()->route($route, [$organization, 'tab' => $tab])
            : redirect()->back();

        return $redirect->with('status', __($message));
    }
}
