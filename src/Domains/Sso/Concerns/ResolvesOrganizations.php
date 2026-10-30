<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Concerns;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Turns an organization slug from input into Roster's organization.
 */
trait ResolvesOrganizations
{
    /**
     * From a slug, or an organization the caller already has (no query).
     */
    private function organizationFrom(mixed $slug): ?OrganizationModel
    {
        if ($slug instanceof OrganizationModel) {
            return $slug;
        }

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return OrganizationModel::query()->where('slug', $slug)->first()
            ?? throw ValidationException::withMessages(['organization' => __('guardian::guardian.unknown_organization')]);
    }
}
