<script setup lang="ts">
import type { AtriumDetailsItem } from '@refactor-circus/atrium'
import { contextValue } from '../../components/admin/admin'
import { userPath } from '../../components/access/access'
import type { GuardianSecurityEvent } from '../../components/security/security'
import GuardianState from '../../components/two-factor/GuardianState.vue'
import { formatDate, guardianData, useGuardianLoad, type GuardianOne } from '../../components/two-factor/guardian'

/**
 * `guardian.security-event`: one security event
 * (`guardian.security-events.show`, AC 91): its type, user, address, agent,
 * identifier hash and time, and its context as key-value rows.
 */
const { t } = useI18n()
const route = useRoute()

const id = computed(() => String(route.params.event))

const { data: event, pending, failed, status } = useGuardianLoad(async guardian =>
  guardianData<GuardianOne<GuardianSecurityEvent>>(await guardian.GET('/security-events/{event}', { params: { path: { event: id.value } } })).data, id)

const context = computed(() => Object.entries(event.value?.context ?? {}))

const facts = computed<AtriumDetailsItem[]>(() => {
  const current = event.value

  return current
    ? [
        { label: t('guardian.admin.events.fields.type'), value: current.type, mono: true, testid: 'guardian-event-type' },
        { label: t('guardian.admin.events.fields.user'), value: current.user_id, href: current.user_id ? userPath(current.user_id) : null, testid: 'guardian-event-user' },
        { label: t('guardian.admin.events.fields.ip'), value: current.ip },
        { label: t('guardian.admin.event.user_agent'), value: current.user_agent },
        { label: t('guardian.admin.event.identifier_hash'), value: current.identifier_hash, mono: true },
        { label: t('guardian.admin.events.fields.occurred_at'), value: formatDate(current.occurred_at) }
      ]
    : []
})

// No `key`: a context key names no slot of <AtriumDetails>.
const contextItems = computed<AtriumDetailsItem[]>(() => context.value.map(([key, value]) => ({
  label: key,
  value: contextValue(value),
  mono: true,
  testid: `guardian-event-context-${key}`
})))

useHead({ title: () => event.value?.type ?? t('guardian.admin.event.title') })
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar :title="event?.type ?? t('guardian.admin.event.title')">
        <template #leading>
          <UButton to="/guardian/security-events" variant="ghost" icon="i-lucide-arrow-left" :aria-label="t('guardian.admin.events.title')" data-testid="guardian-event-back" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <AtriumIntegrationScreen screen-key="guardian.security-event" :record="id">
        <GuardianState :ready="!pending && event !== null" :failed="failed" :status="status" :not-found="t('guardian.admin.event.not_found')">
          <div v-if="event" class="space-y-4" data-testid="guardian-security-event">
            <UCard>
              <AtriumDetails :items="facts" />
            </UCard>
            <UCard>
              <template #header>
                <h2 class="font-semibold">{{ t('guardian.admin.event.context') }}</h2>
              </template>
              <AtriumState v-if="context.length === 0" compact :title="t('guardian.admin.event.no_context')" data-testid="guardian-event-context-empty" />
              <AtriumDetails v-else :items="contextItems" data-testid="guardian-event-context" />
            </UCard>
          </div>
        </GuardianState>
      </AtriumIntegrationScreen>
    </template>
  </UDashboardPanel>
</template>
