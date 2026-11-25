<script setup lang="ts">
import type { AtriumDetailsItem } from '@refactor-circus/atrium'
import type { GuardianPasswordPolicy } from '../../components/admin/admin'
import GuardianState from '../../components/two-factor/GuardianState.vue'
import { guardianData, useGuardianLoad, type GuardianOne } from '../../components/two-factor/guardian'

/**
 * `guardian.password-policy` (AC 94): Guardian's password rules
 * (`guardian.password-policy.show`), read-only, since they come from
 * Guardian's config: the length bounds, the composition rules, the breach
 * check, the history and the maximum age.
 */
const { t } = useI18n()

useHead({ title: () => t('guardian.admin.password_policy.title') })

const { data: policy, pending, failed, status } = useGuardianLoad(async guardian =>
  guardianData<GuardianOne<GuardianPasswordPolicy>>(await guardian.GET('/password-policy')).data)

const details = computed<AtriumDetailsItem[]>(() => {
  const current = policy.value

  if (!current) {
    return []
  }

  return [
    { label: t('guardian.admin.password_policy.length'), value: t('guardian.admin.password_policy.length_value', { min: current.min_length, max: current.max_length }), testid: 'guardian-password-length' },
    ...(['letters', 'mixed_case', 'numbers', 'symbols'] as const).map(rule => ({
      label: t(`guardian.admin.password_policy.rules.${rule}`),
      value: yesNo(current[rule]),
      testid: `guardian-password-${rule}`
    })),
    {
      label: t('guardian.admin.password_policy.uncompromised'),
      value: current.uncompromised ? t('guardian.admin.password_policy.uncompromised_on', { threshold: current.uncompromised_threshold }) : t('guardian.admin.no'),
      testid: 'guardian-password-uncompromised'
    },
    {
      label: t('guardian.admin.password_policy.history'),
      value: current.history > 0 ? t('guardian.admin.password_policy.history_value', { count: current.history }) : t('guardian.admin.no'),
      testid: 'guardian-password-history'
    },
    {
      label: t('guardian.admin.password_policy.max_age'),
      value: current.max_age_days ? t('guardian.admin.password_policy.max_age_value', { days: current.max_age_days }) : t('guardian.admin.never'),
      testid: 'guardian-password-max-age'
    }
  ]
})

function yesNo(value: boolean): string {
  return value ? t('guardian.admin.yes') : t('guardian.admin.no')
}
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar :title="t('guardian.admin.password_policy.title')" />
    </template>
    <template #body>
      <AtriumIntegrationScreen screen-key="guardian.password-policy">
        <GuardianState :ready="!pending && policy !== null" :failed="failed" :status="status">
          <UCard v-if="policy" data-testid="guardian-password-policy">
            <template #header>
              <p class="text-sm text-muted">{{ t('guardian.admin.password_policy.read_only') }}</p>
            </template>
            <AtriumDetails :items="details" />
          </UCard>
        </GuardianState>
      </AtriumIntegrationScreen>
    </template>
  </UDashboardPanel>
</template>
