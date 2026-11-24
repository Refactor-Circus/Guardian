<script setup lang="ts">
import GuardianEventList from '../../components/admin/GuardianEventList.vue'
import { endOfDay, SECURITY_EVENT_TYPES, startOfDay } from '../../components/admin/admin'
import { blankToNull } from '../../components/access/access'

/**
 * `guardian.security-events`: Guardian's security log
 * (`guardian.security-events.index`, AC 91), newest first and cursor-paged,
 * filtered by `type` (a select of every `SecurityEventType`, D-069), `user`,
 * and the days `from` and `to`, sent as the start of the first and the end
 * of the last. Each event links to its page.
 */
const { t } = useI18n()

useHead({ title: () => t('guardian.admin.events.title') })

const types = SECURITY_EVENT_TYPES.map(type => ({ label: type, value: type as string }))
const form = reactive({ type: '', user: '', from: '', to: '' })
const applied = ref<Record<string, string | null>>({})

function apply(): void {
  applied.value = {
    type: blankToNull(form.type),
    user: blankToNull(form.user),
    from: startOfDay(form.from),
    to: endOfDay(form.to)
  }
}

function clear(): void {
  Object.assign(form, { type: '', user: '', from: '', to: '' })
  apply()
}
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar :title="t('guardian.admin.events.title')" />
    </template>
    <template #body>
      <AtriumIntegrationScreen screen-key="guardian.security-events">
        <div class="space-y-4" data-testid="guardian-security-events">
          <form class="flex flex-wrap items-start gap-3" data-testid="guardian-events-filters" @submit.prevent="apply">
            <UFormField :label="t('guardian.admin.events.fields.type')">
              <AtriumSelect v-model="form.type" :items="types" :blank="t('guardian.admin.events.any_type')" class="w-64" data-testid="guardian-events-filter-type" />
            </UFormField>
            <UFormField :label="t('guardian.admin.events.fields.user')">
              <UInput v-model="form.user" class="w-40" data-testid="guardian-events-filter-user" />
            </UFormField>
            <UFormField :label="t('guardian.admin.events.from')">
              <UInput v-model="form.from" type="date" data-testid="guardian-events-filter-from" />
            </UFormField>
            <UFormField :label="t('guardian.admin.events.to')">
              <UInput v-model="form.to" type="date" data-testid="guardian-events-filter-to" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter" class="mt-field-label" data-testid="guardian-events-filter">{{ t('guardian.admin.filter') }}</UButton>
            <UButton variant="ghost" icon="i-lucide-x" class="mt-field-label" data-testid="guardian-events-clear" @click="clear">{{ t('guardian.admin.clear') }}</UButton>
          </form>

          <GuardianEventList :filters="applied" testid="guardian-events" />
        </div>
      </AtriumIntegrationScreen>
    </template>
  </UDashboardPanel>
</template>
