<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import GuardianState from '../two-factor/GuardianState.vue'
import { choice, NONE, unchoice, type Choice } from '../ui'
import { formatDate, guardianData, useGuardianAction, useGuardianLoad, type GuardianClient, type GuardianPage } from '../two-factor/guardian'
import GuardianErrors from './GuardianErrors.vue'
import GuardianPager from './GuardianPager.vue'
import {
  ACCESS_PAGE_SIZE,
  blankToNull,
  pageOf,
  rolePath,
  scopeKeyOf,
  useRoleCatalogue,
  userPath,
  type GuardianAssignment,
  type GuardianScopeType
} from './access'

/**
 * Role assignments (`guardian.role-assignments.*`, AC 89, 90, 98), paged as
 * Guardian pages them: listed by the filters `user`, `role`, `scope_type`
 * and `scope_id`; assigned (user, role, a scope type from the screen's
 * `scope_types` or none for global, and a scope id); and revoked after a
 * confirmation. A fixed `user` (the user screen) or a fixed scope (an
 * organization's tab) narrows the list and the form, with no filters shown
 * for what is fixed. A 422 from the escalation guard lists the permissions
 * it names.
 */
const props = withDefaults(defineProps<{
  scopeTypes: GuardianScopeType[]
  user?: string | null
  scopeType?: string | null
  scopeId?: string | null
  filters?: boolean
  client?: GuardianClient | null
}>(), { user: null, scopeType: null, scopeId: null, filters: false, client: undefined })

const { t } = useI18n()
const action = useGuardianAction()
const revoking = useGuardianAction()

const scoped = computed(() => props.scopeType !== null && props.scopeId !== null)
const page = ref(1)
const filter = reactive({ user: '', role: '', scope_type: '', scope_id: '' })
const applied = ref({ ...filter })
const form = reactive({ user: '', role: '', scope_type: '', scope_id: '' })

const query = computed(() => {
  const values = {
    user: props.user ?? blankToNull(applied.value.user),
    role: blankToNull(applied.value.role),
    scope_type: scoped.value ? props.scopeType : blankToNull(applied.value.scope_type),
    scope_id: scoped.value ? props.scopeId : blankToNull(applied.value.scope_id)
  }

  return {
    ...Object.fromEntries(Object.entries(values).filter(([, value]) => value !== null)),
    per_page: ACCESS_PAGE_SIZE,
    ...(page.value > 1 ? { page: page.value } : {})
  }
})

const load = useGuardianLoad(async guardian =>
  guardianData<GuardianPage<GuardianAssignment>>(await guardian.GET('/role-assignments', { params: { query: query.value } })), query, props.client === undefined ? undefined : props.client)

const { client, data, pending, failed, status, reload } = load
const roles = useRoleCatalogue(client)
const rows = computed(() => data.value?.data ?? [])
const paging = computed(() => pageOf(data.value))

const columns = computed<TableColumn<GuardianAssignment>[]>(() => [
  ...(props.user === null ? [{ id: 'user', header: t('guardian.access.assignments.fields.user') }] : []),
  { id: 'role', header: t('guardian.access.assignments.fields.role') },
  { id: 'scope', header: t('guardian.access.assignments.fields.scope') },
  { id: 'assigned_at', header: t('guardian.access.assignments.fields.assigned_at'), meta: { class: { td: 'text-muted' } } },
  { id: 'actions', meta: { class: { td: 'text-right' } } }
])

const scopeChoices = computed<Choice[]>(() => props.scopeTypes.map(scope => ({ label: scope.key, value: scope.type })))
const filterScopes = computed<Choice[]>(() => [{ label: t('guardian.access.assignments.any_scope'), value: NONE }, ...scopeChoices.value])
const formScopes = computed<Choice[]>(() => [{ label: t('guardian.access.scopes.global'), value: NONE }, ...scopeChoices.value])
const roleChoices = computed<Choice[]>(() => roles.value.map(role => ({ label: role.name, value: role.slug })))

function applyFilters(): void {
  page.value = 1
  applied.value = { ...filter }
}

function clearFilters(): void {
  Object.assign(filter, { user: '', role: '', scope_type: '', scope_id: '' })
  applyFilters()
}

function scopeOf(assignment: GuardianAssignment): string {
  if (!assignment.scope_type) {
    return t('guardian.access.scopes.global')
  }

  return `${scopeKeyOf(assignment.scope_type, props.scopeTypes)} ${assignment.scope_id ?? ''}`.trim()
}

async function assign(): Promise<void> {
  if (!client) {
    return
  }

  const scopeType = scoped.value ? props.scopeType : blankToNull(form.scope_type)
  const ok = await action.run(async () => {
    guardianData(await client.POST('/role-assignments', {
      body: {
        user: props.user ?? form.user.trim(),
        role: form.role,
        scope_type: scopeType,
        scope_id: scoped.value ? props.scopeId : scopeType === null ? null : blankToNull(form.scope_id)
      }
    }))
  })

  if (ok) {
    Object.assign(form, { user: '', role: '', scope_type: '', scope_id: '' })
    await reload()
  }
}

async function revoke(assignment: GuardianAssignment): Promise<void> {
  const name = assignment.role?.name ?? assignment.id

  if (!client || !window.confirm(t('guardian.access.assignments.confirm_revoke', { role: name, user: assignment.user_id }))) {
    return
  }

  if (await revoking.run(async () => guardianData(await client.DELETE('/role-assignments/{assignment}', { params: { path: { assignment: assignment.id } } })))) {
    await reload()
  }
}

function go(next: number): void {
  page.value = next
}
</script>

<template>
  <div class="space-y-4" data-testid="guardian-assignments">
    <form v-if="filters" class="flex flex-wrap items-end gap-3" data-testid="guardian-assignments-filters" @submit.prevent="applyFilters">
      <UFormField v-if="user === null" :label="t('guardian.access.assignments.fields.user')">
        <UInput v-model="filter.user" class="w-40" data-testid="guardian-assignments-filter-user" />
      </UFormField>
      <UFormField :label="t('guardian.access.assignments.fields.role')">
        <UInput v-model="filter.role" class="w-40" data-testid="guardian-assignments-filter-role" />
      </UFormField>
      <template v-if="!scoped">
        <UFormField :label="t('guardian.access.assignments.fields.scope_type')">
          <USelect :model-value="choice(filter.scope_type)" :items="filterScopes" class="w-44" data-testid="guardian-assignments-filter-scope-type" @update:model-value="filter.scope_type = unchoice($event)" />
        </UFormField>
        <UFormField :label="t('guardian.access.assignments.fields.scope_id')">
          <UInput v-model="filter.scope_id" class="w-32" data-testid="guardian-assignments-filter-scope-id" />
        </UFormField>
      </template>
      <UButton type="submit" icon="i-lucide-filter" data-testid="guardian-assignments-filter">{{ t('guardian.access.filter') }}</UButton>
      <UButton variant="ghost" icon="i-lucide-x" data-testid="guardian-assignments-clear" @click="clearFilters">{{ t('guardian.access.clear') }}</UButton>
    </form>

    <GuardianState :ready="!pending && data !== null" :failed="failed" :status="status" :compact="!filters">
      <div class="space-y-4">
        <GuardianErrors :action="revoking" testid="guardian-revoke" />
        <UTable :data="rows" :columns="columns" data-testid="guardian-assignments-table">
          <template #user-cell="{ row }">
            <ULink :to="userPath(row.original.user_id)" class="hover:underline" data-testid="guardian-assignment-user">{{ row.original.user_id }}</ULink>
          </template>
          <template #role-cell="{ row }">
            <div :data-testid="`guardian-assignment-${row.original.id}`">
              <ULink v-if="row.original.role" :to="rolePath(row.original.role.slug)" class="font-medium hover:underline" data-testid="guardian-assignment-role">{{ row.original.role.name }}</ULink>
              <span v-else class="text-muted">—</span>
            </div>
          </template>
          <template #scope-cell="{ row }">{{ scopeOf(row.original) }}</template>
          <template #assigned_at-cell="{ row }">{{ formatDate(row.original.assigned_at) ?? '—' }}</template>
          <template #actions-cell="{ row }">
            <UButton size="xs" color="error" variant="ghost" icon="i-lucide-x" :data-testid="`guardian-assignment-revoke-${row.original.id}`" @click="revoke(row.original)">
              {{ t('guardian.access.assignments.revoke') }}
            </UButton>
          </template>
          <template #empty>
            <AtriumState compact :title="t('guardian.access.assignments.empty')" data-testid="guardian-assignments-empty" />
          </template>
        </UTable>

        <GuardianPager :current="paging.current" :last="paging.last" testid="guardian-assignments" @go="go" />

        <form class="flex flex-wrap items-end gap-3" data-testid="guardian-assign-form" @submit.prevent="assign">
          <UFormField v-if="user === null" :label="t('guardian.access.assignments.fields.user')" :error="action.error('user')" required>
            <UInput v-model="form.user" class="w-40" data-testid="guardian-assign-user" />
          </UFormField>
          <UFormField :label="t('guardian.access.assignments.fields.role')" required>
            <USelect v-model="form.role" :items="roleChoices" :placeholder="t('guardian.access.assignments.pick_role')" class="w-48" data-testid="guardian-assign-role" />
          </UFormField>
          <template v-if="!scoped">
            <UFormField :label="t('guardian.access.assignments.fields.scope_type')">
              <USelect :model-value="choice(form.scope_type)" :items="formScopes" class="w-44" data-testid="guardian-assign-scope-type" @update:model-value="form.scope_type = unchoice($event)" />
            </UFormField>
            <UFormField v-if="form.scope_type" :label="t('guardian.access.assignments.fields.scope_id')" required>
              <UInput v-model="form.scope_id" class="w-32" data-testid="guardian-assign-scope-id" />
            </UFormField>
          </template>
          <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="guardian-assign-submit">{{ t('guardian.access.assignments.assign') }}</UButton>
        </form>
        <GuardianErrors :action="action" testid="guardian-assign" />
      </div>
    </GuardianState>
  </div>
</template>
