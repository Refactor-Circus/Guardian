<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import GuardianState from '../two-factor/GuardianState.vue'
import { formatDate, guardianData, useGuardianLoad, type GuardianCursorPage } from '../two-factor/guardian'
import type { GuardianSecurityEvent } from '../security/security'
import { eventPath, EVENTS_PAGE_SIZE } from './admin'

/**
 * Security events (`guardian.security-events.index`, AC 91, 92), newest
 * first, a cursor page at a time: "older" follows `next_cursor`, "newer"
 * goes back. The filters come from the parent (the events screen's form, or
 * the lockouts screen's fixed `login.locked_out`); a change starts again at
 * the newest page. Each row links to the event's page.
 */
const props = defineProps<{ filters: Record<string, string | null>, testid: string }>()

const { t } = useI18n()

const cursor = ref<string | null>(null)
const previous = ref<(string | null)[]>([])

const query = computed(() => ({
  ...Object.fromEntries(Object.entries(props.filters).filter(([, value]) => value !== null && value !== '')),
  per_page: EVENTS_PAGE_SIZE,
  ...(cursor.value ? { cursor: cursor.value } : {})
}))

watch(() => props.filters, () => {
  cursor.value = null
  previous.value = []
}, { deep: true })

const { data: page, pending, failed, status } = useGuardianLoad(async guardian =>
  guardianData<GuardianCursorPage<GuardianSecurityEvent>>(await guardian.GET('/security-events', { params: { query: query.value as never } })), query)

const events = computed(() => page.value?.data ?? [])

const columns = computed<TableColumn<GuardianSecurityEvent>[]>(() => [
  { accessorKey: 'type', header: t('guardian.admin.events.fields.type') },
  { accessorKey: 'user_id', header: t('guardian.admin.events.fields.user') },
  { accessorKey: 'ip', header: t('guardian.admin.events.fields.ip'), meta: { class: { td: 'text-muted' } } },
  { accessorKey: 'occurred_at', header: t('guardian.admin.events.fields.occurred_at'), meta: { class: { td: 'text-muted' } } }
])

function older(): void {
  const next = page.value?.meta?.next_cursor

  if (next) {
    previous.value = [...previous.value, cursor.value]
    cursor.value = next
  }
}

function newer(): void {
  cursor.value = previous.value.at(-1) ?? null
  previous.value = previous.value.slice(0, -1)
}
</script>

<template>
  <GuardianState :ready="!pending && page !== null" :failed="failed" :status="status">
    <div class="space-y-3" :data-testid="testid">
      <UTable :data="events" :columns="columns">
        <template #type-cell="{ row }">
          <div :data-testid="`guardian-event-${row.original.id}`">
            <ULink :to="eventPath(row.original.id)" class="font-mono hover:underline" data-testid="guardian-event-link">{{ row.original.type }}</ULink>
          </div>
        </template>
        <template #user_id-cell="{ row }">{{ row.original.user_id ?? '—' }}</template>
        <template #ip-cell="{ row }">{{ row.original.ip ?? '—' }}</template>
        <template #occurred_at-cell="{ row }">{{ formatDate(row.original.occurred_at) }}</template>
        <template #empty>
          <AtriumState compact :title="t('guardian.admin.events.empty')" :data-testid="`${testid}-empty`" />
        </template>
      </UTable>
      <div class="flex gap-2">
        <UButton v-if="previous.length > 0" size="xs" color="neutral" variant="soft" :data-testid="`${testid}-newer`" @click="newer">{{ t('guardian.admin.events.newer') }}</UButton>
        <UButton v-if="page?.meta?.next_cursor" size="xs" color="neutral" variant="soft" :data-testid="`${testid}-older`" @click="older">{{ t('guardian.admin.events.older') }}</UButton>
      </div>
    </div>
  </GuardianState>
</template>
