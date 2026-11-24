<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import GuardianErrors from '../access/GuardianErrors.vue'
import GuardianPager from '../access/GuardianPager.vue'
import { pageOf } from '../access/access'
import GuardianState from '../two-factor/GuardianState.vue'
import { guardianData, untypedBody, useGuardianAction, useGuardianLoad, type GuardianClient, type GuardianPage } from '../two-factor/guardian'
import SsoConnectionFields from './SsoConnectionFields.vue'
import { connectionPath, settingsBody, SSO_PAGE_SIZE, SSO_PROTOCOLS, type GuardianSsoConnection, type GuardianSsoField, type GuardianSsoProtocol } from './sso'

/**
 * An organization's SSO connections (the `guardian.sso` tab, AC 96), ported
 * from v1's `organization-sso` view: the list
 * (`guardian.organizations.sso-connections.index`, a page at a time), each
 * linked to its page, and a new connection (`.store`) with its `name`,
 * `protocol` and that protocol's settings (D-072), which opens the new
 * connection's page. A 403 shows the no-access state.
 */
const props = withDefaults(defineProps<{ organization: string, client?: GuardianClient | null }>(), { client: undefined })

const { t } = useI18n()
const create = useGuardianAction()

const page = ref(1)
const query = computed(() => ({ per_page: SSO_PAGE_SIZE, ...(page.value > 1 ? { page: page.value } : {}) }))

const { client, data, pending, failed, status } = useGuardianLoad(async guardian =>
  guardianData<GuardianPage<GuardianSsoConnection>>(await guardian.GET('/organizations/{organization}/sso-connections', {
    params: { path: { organization: props.organization }, query: query.value }
  })), () => [props.organization, query.value], props.client === undefined ? undefined : props.client)

const rows = computed(() => data.value?.data ?? [])
const paging = computed(() => pageOf(data.value))
const columns = computed<TableColumn<GuardianSsoConnection>[]>(() => [
  { id: 'name', header: t('guardian.sso.fields.name') },
  { id: 'protocol', header: t('guardian.sso.fields.protocol') },
  { id: 'identities', header: t('guardian.sso.identities.title'), meta: { class: { th: 'text-right', td: 'text-right tabular-nums' } } }
])
const protocols = computed(() => SSO_PROTOCOLS.map(protocol => ({ label: t(`guardian.sso.protocols.${protocol}`), value: protocol })))
const form = reactive({ name: '', protocol: 'oidc' as GuardianSsoProtocol })
const settings = reactive<Partial<Record<GuardianSsoField, string>>>({})

async function add(): Promise<void> {
  if (!client) {
    return
  }

  const created: { connection: GuardianSsoConnection | null } = { connection: null }
  const ok = await create.run(async () => {
    created.connection = guardianData<{ data: GuardianSsoConnection }>(await client.POST('/organizations/{organization}/sso-connections', {
      params: { path: { organization: props.organization } },
      body: untypedBody({ name: form.name.trim(), protocol: form.protocol, ...settingsBody(form.protocol, settings) })
    })).data
  })

  if (ok && created.connection) {
    await navigateTo(connectionPath(created.connection.slug))
  }
}

function go(next: number): void {
  page.value = next
}
</script>

<template>
  <div class="space-y-4" data-testid="guardian-sso-connections">
    <GuardianState :ready="!pending && data !== null" :failed="failed" :status="status" compact>
      <div class="space-y-4">
        <UTable :data="rows" :columns="columns">
          <template #name-cell="{ row }">
            <div class="flex flex-wrap items-center gap-2" :data-testid="`guardian-sso-connection-${row.original.slug}`">
              <ULink :to="connectionPath(row.original.slug)" class="font-medium hover:underline" data-testid="guardian-sso-connection-link">{{ row.original.name }}</ULink>
              <UBadge v-if="row.original.enforced" color="warning" variant="subtle">{{ t('guardian.sso.enforced_badge') }}</UBadge>
              <UBadge v-if="!row.original.enabled" color="neutral" variant="subtle">{{ t('guardian.sso.disabled_badge') }}</UBadge>
            </div>
          </template>
          <template #protocol-cell="{ row }">{{ t(`guardian.sso.protocols.${row.original.protocol}`, row.original.protocol) }}</template>
          <template #identities-cell="{ row }">{{ row.original.identities_count ?? '—' }}</template>
          <template #empty>
            <AtriumState compact :title="t('guardian.sso.connections.empty')" data-testid="guardian-sso-connections-empty" />
          </template>
        </UTable>

        <GuardianPager :current="paging.current" :last="paging.last" testid="guardian-sso-connections" @go="go" />

        <UCard>
          <template #header>
            <h3 class="font-medium">{{ t('guardian.sso.connections.new') }}</h3>
          </template>
          <form class="space-y-3" data-testid="guardian-sso-new-form" @submit.prevent="add">
            <div class="flex flex-wrap items-end gap-3">
              <UFormField :label="t('guardian.sso.fields.name')" :error="create.error('name')" required>
                <UInput v-model="form.name" class="w-56" data-testid="guardian-sso-new-name" />
              </UFormField>
              <UFormField :label="t('guardian.sso.fields.protocol')" :error="create.error('protocol')" required>
                <USelect v-model="form.protocol" :items="protocols" class="w-56" data-testid="guardian-sso-new-protocol" />
              </UFormField>
            </div>
            <SsoConnectionFields :protocol="form.protocol" :values="settings" :action="create" testid="guardian-sso-new" />
            <GuardianErrors v-if="Object.keys(create.errors.value).length === 0" :action="create" testid="guardian-sso-new" />
            <UButton type="submit" icon="i-lucide-plus" :loading="create.busy.value" data-testid="guardian-sso-new-submit">{{ t('guardian.sso.connections.create') }}</UButton>
          </form>
        </UCard>
      </div>
    </GuardianState>
  </div>
</template>
