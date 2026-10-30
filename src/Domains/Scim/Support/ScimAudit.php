<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Support;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * What the audit log (refactor-circus/keen) records about the SCIM domain's
 * events, through Keystone's AuditHooks: the organization Roster scoped them
 * to before they moved here (criterion 15). The subject stays the event's
 * own model (a token, a SCIM user or group).
 *
 * Registered whether or not Keen is installed; the hook costs nothing until
 * it records. It answers only for this domain's own events.
 */
final readonly class ScimAudit
{
    private const string EVENTS = 'RefactorCircus\\Guardian\\Domains\\Scim\\Events\\';

    public function register(AuditHooks $hooks): void
    {
        $hooks->scope(fn (object $event, array $models, ?Model $subject): ?Model => $this->ownsEvent($event) ? $this->organization($models, $subject) : null);
    }

    /**
     * The event's organization, else the subject's own organization.
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

        $id = $subject?->getAttribute('organization_id');

        return is_int($id) || (is_string($id) && $id !== '')
            ? OrganizationModel::withTrashed()->find($id)
            : null;
    }

    private function ownsEvent(object $event): bool
    {
        return str_starts_with($event::class, self::EVENTS);
    }
}
