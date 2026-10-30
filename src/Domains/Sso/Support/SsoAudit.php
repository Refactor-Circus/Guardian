<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Users;

/**
 * What the audit log (refactor-circus/keen) records about the SSO domain's
 * events, through Keystone's AuditHooks: the subject and organization Roster
 * gave them before they moved here (criterion 15).
 *
 * Registered whether or not Keen is installed; the hooks cost nothing until
 * it records. They answer only for this domain's own events.
 */
final readonly class SsoAudit
{
    private const string EVENTS = 'RefactorCircus\\Guardian\\Domains\\Sso\\Events\\';

    public function __construct(
        private Users $users,
        private Roster $roster,
    ) {}

    public function register(AuditHooks $hooks): void
    {
        $hooks->subject(fn (object $event, array $models): ?Model => $this->ownsEvent($event) ? $this->subject($models) : null);
        $hooks->scope(fn (object $event, array $models, ?Model $subject): ?Model => $this->ownsEvent($event) ? $this->organization($models, $subject) : null);
    }

    /**
     * A sign-in or a link is about the user it signed in or linked; anything
     * else is left to the audit log (the event's first model).
     *
     * @param  array<string, Model>  $models
     */
    private function subject(array $models): ?Model
    {
        return $models['user'] ?? null;
    }

    /**
     * The event's organization, else the user's current one for an entry
     * about a user, else the subject's own organization.
     *
     * @param  array<string, Model>  $models
     */
    private function organization(array $models, ?Model $subject): ?OrganizationModel
    {
        foreach ($models as $model) {
            if ($model instanceof OrganizationModel) {
                return $model;
            }
        }

        if ($subject === null) {
            return null;
        }

        $user = $this->userModel();

        if ($user !== null && $subject instanceof $user) {
            return $this->roster->organization($subject);
        }

        $id = $subject->getAttribute('organization_id');

        return is_int($id) || (is_string($id) && $id !== '')
            ? OrganizationModel::withTrashed()->find($id)
            : null;
    }

    /**
     * The user model Roster manages, or null while `roster.users.model` is
     * not set up yet.
     *
     * @return class-string<Model>|null
     */
    private function userModel(): ?string
    {
        try {
            return $this->users->model();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function ownsEvent(object $event): bool
    {
        return str_starts_with($event::class, self::EVENTS);
    }
}
