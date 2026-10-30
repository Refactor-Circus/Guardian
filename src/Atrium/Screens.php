<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Atrium;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Guardian\Domains\Scim\Actions\ListScimTokensAction;
use RefactorCircus\Guardian\Domains\Scim\Data\ListScimTokensData;
use RefactorCircus\Guardian\Domains\Sso\Actions\ListSsoConnectionsAction;
use RefactorCircus\Guardian\Domains\Sso\Actions\ListSsoIdentitiesAction;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Users;

/**
 * Guardian's tabs and sections, and where they go: `guardian.atrium.screens`
 * maps a screen key (or a `{package}.*` / `*` target) to a list in which a
 * plain value is a tab key and a `slot => key` entry is a section key. No
 * other code names a host screen.
 *
 * A bad entry (an unknown key, a target that is not a string, or one Atrium
 * rejects) throws an InvalidArgumentException naming the target and key in
 * the local and testing environments. Elsewhere it is reported once, skipped,
 * and the rest registers (D-021).
 */
final class Screens
{
    public const string SSO = 'guardian.sso';

    public const string SCIM = 'guardian.scim';

    public const string SSO_IDENTITIES = 'guardian.sso-identities';

    /**
     * Register each placed tab and section on the registry.
     */
    public static function register(ScreenExtensions $registry, mixed $placements): void
    {
        if (! is_array($placements)) {
            self::fail(new InvalidArgumentException('Guardian\'s guardian.atrium.screens must map screen keys to lists of Guardian tab and section keys.'));

            return;
        }

        $tabs = self::tabs();
        $sections = self::sections();

        foreach ($placements as $target => $entries) {
            if (! is_array($entries)) {
                self::fail(new InvalidArgumentException(sprintf('Guardian cannot place [%s] [%s]: the entry must be a list of Guardian tab and section keys.', $target, self::describe($entries))));

                continue;
            }

            foreach ($entries as $slot => $key) {
                self::place($registry, $target, $slot, $key, $tabs, $sections);
            }
        }
    }

    /**
     * Guardian's tabs, by key.
     *
     * @return array<string, TabDefinition>
     */
    public static function tabs(): array
    {
        return [
            self::SSO => TabDefinition::make(self::SSO)
                ->label(__('guardian::guardian.sso'))
                ->icon('arrow-right-end-on-rectangle')
                ->view('guardian::atrium.organization-sso')
                ->permission('guardian.sso.view')
                ->resolve(fn (?Model $record, Screen $screen): array => [
                    'connections' => self::connections(self::organization($record), 25, request()->integer('page', 1))->withQueryString(),
                    'organization' => $record,
                ]),
            self::SCIM => TabDefinition::make(self::SCIM)
                ->label(__('guardian::guardian.scim'))
                ->icon('server-stack')
                ->view('guardian::atrium.organization-scim')
                ->permission('guardian.scim.manage')
                ->resolve(function (?Model $record, Screen $screen): array {
                    $organization = self::organization($record);

                    return [
                        'tokens' => app(ListScimTokensAction::class)->execute($organization, new ListScimTokensData(perPage: 25, page: request()->integer('page', 1)))->withQueryString(),
                        'baseUrl' => url(trim((string) config('guardian.scim.prefix', 'scim/v2'), '/').'/'.$organization->slug),
                        'organization' => $record,
                        // The token form's "link to SSO connection" options.
                        'connections' => self::connections($organization, 100),
                    ];
                }),
        ];
    }

    /**
     * Guardian's sections, by key.
     *
     * @return array<string, SectionDefinition>
     */
    public static function sections(): array
    {
        return [
            self::SSO_IDENTITIES => SectionDefinition::make(self::SSO_IDENTITIES)
                ->label(__('guardian::guardian.sso_identities'))
                ->view('guardian::atrium.user-sso-identities')
                // Shown only when the user has an identity: one exists query.
                ->authorize(fn (Request $request, ?Model $record, Screen $screen): bool => self::isUser($record)
                    && SsoIdentityModel::query()->where('user_id', $record->getKey())->exists())
                ->resolve(fn (?Model $record, Screen $screen): array => [
                    'identities' => app(ListSsoIdentitiesAction::class)->execute(new ListSsoIdentitiesData(user: $record, perPage: 50)),
                ]),
        ];
    }

    /**
     * @param  array<string, TabDefinition>  $tabs
     * @param  array<string, SectionDefinition>  $sections
     */
    private static function place(ScreenExtensions $registry, int|string $target, int|string $slot, mixed $key, array $tabs, array $sections): void
    {
        $name = sprintf('[%s] [%s]', $target, self::describe($key));

        if (! is_string($target)) {
            self::fail(new InvalidArgumentException("Guardian cannot place {$name}: the target must be a screen key, such as package.screen."));

            return;
        }

        $definition = is_string($key) ? (is_int($slot) ? ($tabs[$key] ?? null) : ($sections[$key] ?? null)) : null;

        if ($definition === null) {
            self::fail(new InvalidArgumentException(is_int($slot)
                ? "Guardian cannot place {$name}: Guardian has no tab with this key (".implode(', ', array_keys($tabs)).').'
                : "Guardian cannot place {$name}: Guardian has no section with this key (".implode(', ', array_keys($sections)).').'));

            return;
        }

        try {
            if ($definition instanceof TabDefinition) {
                $registry->tab($target, $definition);
            } else {
                $registry->section($target, (string) $slot, $definition);
            }
        } catch (InvalidArgumentException $exception) {
            self::fail(new InvalidArgumentException("Guardian cannot place {$name}: {$exception->getMessage()}", previous: $exception));
        }
    }

    /**
     * Throw where a developer will see it; elsewhere report and carry on.
     */
    private static function fail(InvalidArgumentException $exception): void
    {
        if (app()->environment(['local', 'testing'])) {
            throw $exception;
        }

        report($exception);
    }

    private static function describe(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : get_debug_type($value);
    }

    /**
     * @phpstan-assert-if-true Model $record
     */
    private static function isUser(?Model $record): bool
    {
        $users = app(Users::class)->model();

        return $record instanceof $users;
    }

    /**
     * The organization a tab's record is, or belongs to.
     */
    private static function organization(?Model $record): OrganizationModel
    {
        $organization = $record instanceof OrganizationModel ? $record : $record?->getAttribute('organization');

        abort_unless($organization instanceof OrganizationModel, 404);

        return $organization;
    }

    /**
     * @return LengthAwarePaginator<int, SsoConnectionModel>
     */
    private static function connections(OrganizationModel $organization, int $perPage, ?int $page = null): LengthAwarePaginator
    {
        return app(ListSsoConnectionsAction::class)->execute(new ListSsoConnectionsData(organization: $organization, perPage: $perPage, page: $page));
    }
}
