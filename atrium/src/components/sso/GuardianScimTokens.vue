<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import GuardianErrors from '../access/GuardianErrors.vue'
import GuardianPager from '../access/GuardianPager.vue'
import { pageOf } from '../access/access'
import GuardianState from '../two-factor/GuardianState.vue'
import { formatDate, guardianData, useGuardianAction, useGuardianLoad, type GuardianClient, type GuardianPage } from '../two-factor/guardian'
import { useAtriumIntegration } from '@refactor-circus/atrium'
import { choice, NONE, unchoice, type Choice } from '../ui'
import { CONNECTION_CHOICES, scimBaseUrl, SSO_PAGE_SIZE, type GuardianScimToken, type GuardianSsoConnection } from './sso'

/**
 * An organization's SCIM tokens (the `guardian.scim` tab, AC 96), ported
 * from v1's `organization-scim` view: the SCIM base URL an identity
 * provider is given (D-058); the tokens
 * (`guardian.organizations.scim-tokens.index`, a page at a time); a new
 * token (`.store`: `name`, `expires_in_days` and an optional SSO connection
 * of the organization), whose plain value shows once, with copy, and lives
 * only in this component's state; and revoking one
 * (`guardian.scim-tokens.destroy`) after a confirmation.
 */
const props = withDefaults(defineProps<{ organization: string, scimPath?: string | null, client?: GuardianClient | null }>(), { scimPath: null, client: undefined })

const { t } = useI18n()
const issue = useGuardianAction()
const revoking = useGuardianAction()
const { apiBase } = useAtriumIntegration('guardian')

const baseUrl = computed(() => scimBaseUrl(apiBase, props.scimPath, props.organization))

const page = ref(1)
const query = computed(() => ({ per_page: SSO_PAGE_SIZE, ...(page.value > 1 ? { page: page.value } : {}) }))

const { client, data, pending, failed, status, reload } = useGuardianLoad(async guardian =>
  guardianData<GuardianPage<GuardianScimToken>>(await guardian.GET('/organizations/{organization}/scim-tokens', {
    params: { path: { organization: props.organization }, query: query.value }
  })), () => [props.organization, query.value], props.client === undefined ? undefined : props.client)

const connections = shallowRef<GuardianSsoConnection[]>([])

if (client) {
  void client.GET('/organizations/{organization}/sso-connections', { params: { path: { organization: props.organization }, query: { per_page: CONNECTION_CHOICES } } })
    .then(result => connections.value = guardianData<GuardianPage<GuardianSsoConnection>>(result).data)
    .catch(() => connections.value = [])
}

const rows = computed(() => data.value?.data ?? [])
const columns = computed<TableColumn<GuardianScimToken>[]>(() => [
  { id: 'name', header: t('guardian.sso.fields.name') },
  { id: 'connection', header: t('guardian.sso.scim.connection') },
  { id: 'last_used', header: t('guardian.sso.scim.last_used'), meta: { class: { td: 'text-muted' } } },
  { id: 'expires', header: t('guardian.sso.scim.expires'), meta: { class: { td: 'text-muted' } } },
  { id: 'actions', meta: { class: { td: 'text-right' } } }
])
const connectionChoices = computed<Choice[]>(() => [
  { label: t('guardian.sso.scim.no_connection'), value: NONE },
  ...connections.value.map(connection => ({ label: connection.name, value: connection.slug }))
])
const paging = computed(() => pageOf(data.value))
const form = reactive({ name: '', expires_in_days: '' as string | number, sso_connection: '' })
const plain = ref<string | null>(null)
const copied = ref(false)

onBeforeUnmount(() => {
  plain.value = null
})

function days(value: string | number): number | null {
  const number = Number(value)

  return value === '' || !Number.isFinite(number) ? null : Math.trunc(number)
}

async function add(): Promise<void> {
  if (!client) {
    return
  }

  plain.value = null
  copied.value = false

  const issued: { token: string | null } = { token: null }
  const ok = await issue.run(async () => {
    issued.token = guardianData<{ token: string }>(await client.POST('/organizations/{organization}/scim-tokens', {
      params: { path: { organization: props.organization } },
      body: { name: form.name.trim(), expires_in_days: days(form.expires_in_days), sso_connection: form.sso_connection === '' ? null : form.sso_connection }
    })).token
  })

  if (ok) {
    plain.value = issued.token
    Object.assign(form, { name: '', expires_in_days: '', sso_connection: '' })
    await reload()
  }
}

async function copy(): Promise<void> {
  if (plain.value) {
    await navigator.clipboard?.writeText(plain.value)
    copied.value = true
  }
}

async function revoke(token: GuardianScimToken): Promise<void> {
  if (!client || !window.confirm(t('guardian.sso.scim.confirm_revoke', { name: token.name }))) {
    return
  }

  if (await revoking.run(async () => guardianData(await client.DELETE('/scim-tokens/{token}', { params: { path: { token: token.id } } })))) {
    await reload()
  }
}

function go(next: number): void {
  page.value = next
}
</script>

<template>
  <div class="space-y-4" data-testid="guardian-scim-tokens">
    <UAlert v-if="plain" color="warning" variant="subtle" :title="t('guardian.sso.scim.created')" data-testid="guardian-scim-plain">
      <template #description>
        <div class="flex flex-wrap items-center gap-2">
          <code class="break-all font-mono text-sm" data-testid="guardian-scim-plain-token">{{ plain }}</code>
          <UButton size="xs" variant="soft" icon="i-lucide-copy" data-testid="guardian-scim-copy" @click="copy">{{ copied ? t('guardian.sso.copied') : t('guardian.sso.copy') }}</UButton>
        </div>
      </template>
    </UAlert>

    <UCard>
      <template #header>
        <h3 class="font-medium">{{ t('guardian.sso.scim.base_url') }}</h3>
      </template>
      <code class="break-all font-mono text-sm" data-testid="guardian-scim-base-url">{{ baseUrl ?? '—' }}</code>
    </UCard>

    <GuardianState :ready="!pending && data !== null" :failed="failed" :status="status" compact>
      <div class="space-y-4">
        <GuardianErrors :action="revoking" testid="guardian-scim-revoke" />
        <UTable :data="rows" :columns="columns">
          <template #name-cell="{ row }">
            <div class="flex flex-wrap items-center gap-2" :data-testid="`guardian-scim-token-${row.original.id}`">
              {{ row.original.name }}
              <UBadge v-if="row.original.revoked_at" color="neutral" variant="subtle">{{ t('guardian.sso.scim.revoked') }}</UBadge>
              <UBadge v-else-if="!row.original.usable" color="neutral" variant="subtle">{{ t('guardian.sso.scim.expired') }}</UBadge>
            </div>
          </template>
          <template #connection-cell="{ row }">{{ row.original.sso_connection ?? '—' }}</template>
          <template #last_used-cell="{ row }">{{ formatDate(row.original.last_used_at) ?? t('guardian.sso.never') }}</template>
          <template #expires-cell="{ row }">{{ formatDate(row.original.expires_at) ?? t('guardian.sso.never') }}</template>
          <template #actions-cell="{ row }">
            <UButton v-if="!row.original.revoked_at" size="xs" color="error" variant="ghost" icon="i-lucide-ban" :data-testid="`guardian-scim-revoke-${row.original.id}`" @click="revoke(row.original)">{{ t('guardian.sso.scim.revoke') }}</UButton>
          </template>
          <template #empty>
            <AtriumState compact :title="t('guardian.sso.scim.empty')" data-testid="guardian-scim-empty" />
          </template>
        </UTable>

        <GuardianPager :current="paging.current" :last="paging.last" testid="guardian-scim-tokens" @go="go" />

        <UCard>
          <template #header>
            <h3 class="font-medium">{{ t('guardian.sso.scim.new') }}</h3>
          </template>
          <form class="flex flex-wrap items-end gap-3" data-testid="guardian-scim-form" @submit.prevent="add">
            <UFormField :label="t('guardian.sso.fields.name')" :error="issue.error('name')" required>
              <UInput v-model="form.name" class="w-56" data-testid="guardian-scim-name" />
            </UFormField>
            <UFormField :label="t('guardian.sso.scim.expires_in_days')" :error="issue.error('expires_in_days')">
              <UInput v-model="form.expires_in_days" type="number" min="1" max="3650" class="w-32" data-testid="guardian-scim-expires" />
            </UFormField>
            <UFormField :label="t('guardian.sso.scim.link_connection')" :error="issue.error('sso_connection')">
              <USelect :model-value="choice(form.sso_connection)" :items="connectionChoices" class="w-56" data-testid="guardian-scim-connection" @update:model-value="form.sso_connection = unchoice($event)" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-plus" :loading="issue.busy.value" data-testid="guardian-scim-submit">{{ t('guardian.sso.scim.create') }}</UButton>
          </form>
          <div class="mt-3">
            <GuardianErrors v-if="Object.keys(issue.errors.value).length === 0" :action="issue" testid="guardian-scim-issue" />
          </div>
        </UCard>
      </div>
    </GuardianState>
  </div>
</template>
