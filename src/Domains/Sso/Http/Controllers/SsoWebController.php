<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Sso\Actions\LinkSsoIdentityAction;
use RefactorCircus\Guardian\Domains\Sso\Actions\SsoLoginAction;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoLoginFailedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Services\Sso;
use SocialiteProviders\Saml2\Provider;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The browser side of single sign-on: sending people to their identity
 * provider and handling what comes back.
 */
final class SsoWebController
{
    private const string INTENT = 'guardian.sso.intent';

    public function __construct(private readonly Sso $sso) {}

    /**
     * Find the organization's identity provider from an email address.
     */
    public function discover(Request $request): RedirectResponse
    {
        $email = (string) $request->validate(['email' => ['required', 'email']])['email'];
        $domain = strtolower(substr($email, (int) strrpos($email, '@') + 1));

        $connection = SsoConnectionModel::query()
            ->where('enabled', true)
            ->whereHas('organization.domains', fn (Builder $query): Builder => $query->where('domain', $domain))
            ->orderByDesc('enforced')
            ->first();

        if ($connection === null) {
            throw ValidationException::withMessages(['email' => __('guardian::guardian.sso_not_found')]);
        }

        return redirect()->route('guardian.sso.start', $connection->slug);
    }

    public function start(Request $request, string $connection): Response
    {
        $model = $this->connection($connection);
        $request->session()->put(self::INTENT, 'login');

        return $this->sso->provider($model)->redirect();
    }

    /**
     * Prove an identity at the provider and link it to the signed-in account.
     */
    public function link(Request $request, string $connection): Response
    {
        $model = $this->connection($connection);
        $request->session()->put(self::INTENT, 'link');

        return $this->sso->provider($model)->redirect();
    }

    public function callback(Request $request, string $connection): RedirectResponse
    {
        $model = $this->connection($connection);
        $intent = $request->session()->pull(self::INTENT, 'login');

        try {
            $claims = $this->sso->claims($model, $this->sso->provider($model)->user());
        } catch (Throwable) {
            SsoLoginFailedActionEvent::dispatch($model, null, 'invalid_response');

            return $this->failed(__('guardian::guardian.sso_failed_invalid_response'));
        }

        try {
            if ($intent === 'link') {
                $user = $request->user();
                abort_unless($user instanceof Model, 401);

                app(LinkSsoIdentityAction::class)->execute($user, $model, $claims);

                return redirect((string) config('guardian.sso.redirect', '/'))->with('status', __('guardian::guardian.sso_linked'));
            }

            [$user] = app(SsoLoginAction::class)->execute($model, $claims);
        } catch (ValidationException $exception) {
            return $this->failed((string) collect($exception->errors())->flatten()->first());
        }

        if ($user instanceof Authenticatable) {
            Auth::guard()->login($user);
            $request->session()->regenerate();
        }

        return redirect()->intended((string) config('guardian.sso.redirect', '/'));
    }

    /**
     * SAML service provider metadata for the identity provider's admin.
     */
    public function metadata(string $connection): Response
    {
        $model = $this->connection($connection);
        abort_unless($model->protocol === SsoConnectionModel::SAML, 404);

        /** @var Provider $provider */
        $provider = $this->sso->provider($model);

        return $provider->getServiceProviderMetadata();
    }

    private function connection(string $slug): SsoConnectionModel
    {
        $connection = SsoConnectionModel::query()->where('slug', $slug)->firstOrFail();

        abort_unless($connection->enabled, 404);

        return $connection;
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect((string) config('guardian.sso.redirect', '/'))->withErrors(['sso' => $message]);
    }
}
