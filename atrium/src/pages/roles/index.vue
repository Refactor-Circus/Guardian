<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import GuardianErrors from '../../components/access/GuardianErrors.vue'
import GuardianPager from '../../components/access/GuardianPager.vue'
import GuardianRolePermissions from '../../components/access/GuardianRolePermissions.vue'
import {
  ACCESS_PAGE_SIZE,
  blankToNull,
  pageOf,
  rolePath,
  ROLE_SCOPES,
  scopeKeyOf,
  scopeTypesOf,
  usePermissionCatalogue,
  type GuardianRole
} from '../../components/access/access'
import GuardianState from '../../components/two-factor/GuardianState.vue'
import { guardianData, useGuardianAction, useGuardianLoad, type GuardianOne, type GuardianPage } from '../../components/two-factor/guardian'

/**
 * `guardian.roles`: every role (`guardian.roles.index`), a page at a time,
 * moved from Roster's `roster.roles` (AC 89, 99). The new-role form sends
 * name, slug, scope (`global`, `any`, or one of the screen's `scope_types`),
 * description and permissions to `guardian.roles.store`, then opens the new
 * role. A 422 from the escalation guard lists the permissions it names.
 */
const { t } = useI18n()
const router = useRouter()
const action = useGuardianAction()

useHead({ title: () => t('guardian.access.roles.title') })

const { data: screen } = useAtriumScreen('guardian.roles')
const scopeTypes = computed(() => scopeTypesOf(screen.value?.data.options as Record<string, unknown> | undefined))

const page = ref(1)
const query = computed(() => ({ per_page: ACCESS_PAGE_SIZE, ...(page.value > 1 ? { page: page.value } : {}) }))

const { client, data, pending, failed, status } = useGuardianLoad(async guardian =>
  guardianData<GuardianPage<GuardianRole>>(await guardian.GET('/roles', { params: { query: query.value } })), query)

const permissions = usePermissionCatalogue(client)
const roles = computed(() => data.value?.data ?? [])
const paging = computed(() => pageOf(data.value))
const columns = computed<TableColumn<GuardianRole>[]>(() => [
  { id: 'name', header: t('guardian.access.roles.fields.name') },
  { id: 'scope', header: t('guardian.access.roles.fields.scope') },
  { id: 'permissions', header: t('guardian.access.roles.fields.permissions'), meta: { class: { th: 'text-right', td: 'text-right tabular-nums' } } }
])
const scopes = computed(() => [
  ...ROLE_SCOPES.map(scope => ({ label: t(`guardian.access.scopes.${scope}`), value: scope as string })),
  ...scopeTypes.value.map(scope => ({ label: scope.key, value: scope.type }))
])
const form = reactive({ name: '', slug: '', scope: 'global', description: '', permissions: [] as string[] })

function scopeLabel(scope: string): string {
  return (ROLE_SCOPES as readonly string[]).includes(scope) ? t(`guardian.access.scopes.${scope}`) : scopeKeyOf(scope, scopeTypes.value) ?? scope
}

function go(next: number): void {
  page.value = next
}

async function create(): Promise<void> {
  if (!client) {
    return
  }

  const created: GuardianRole[] = []
  const ok = await action.run(async () => {
    created.push(guardianData<GuardianOne<GuardianRole>>(await client.POST('/roles', {
      body: {
        name: form.name.trim(),
        slug: blankToNull(form.slug),
        scope: form.scope,
        description: blankToNull(form.description),
        permissions: form.permissions
      }
    })).data)
  })

  if (ok && created[0]) {
    await router.push(rolePath(created[0].slug))
  }
}
</script>

<template>
  <UDashboardPanel>
    <template #header>
      <UDashboardNavbar :title="t('guardian.access.roles.title')" />
    </template>
    <template #body>
      <AtriumIntegrationScreen screen-key="guardian.roles">
        <GuardianState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4" data-testid="guardian-roles">
            <UTable :data="roles" :columns="columns" data-testid="guardian-roles-table">
              <template #name-cell="{ row }">
                <div class="flex flex-wrap items-center gap-2" :data-testid="`guardian-role-row-${row.original.slug}`">
                  <ULink :to="rolePath(row.original.slug)" class="font-medium hover:underline">{{ row.original.name }}</ULink>
                  <UBadge v-if="row.original.super" color="error" variant="subtle">{{ t('guardian.access.roles.super') }}</UBadge>
                  <UBadge v-if="row.original.system" color="neutral" variant="subtle">{{ t('guardian.access.roles.built_in') }}</UBadge>
                </div>
              </template>
              <template #scope-cell="{ row }">{{ scopeLabel(row.original.scope) }}</template>
              <template #permissions-cell="{ row }">{{ row.original.super ? t('guardian.access.roles.everything') : (row.original.permissions?.length ?? 0) }}</template>
              <template #empty>
                <AtriumState compact :title="t('guardian.access.roles.empty')" data-testid="guardian-roles-empty" />
              </template>
            </UTable>

            <GuardianPager :current="paging.current" :last="paging.last" testid="guardian-roles" @go="go" />

            <UCard>
              <template #header>
                <h2 class="font-medium">{{ t('guardian.access.roles.new') }}</h2>
              </template>
              <form class="flex max-w-3xl flex-col gap-4" data-testid="guardian-role-new-form" @submit.prevent="create">
                <GuardianErrors :action="action" testid="guardian-role-new" />
                <div class="flex flex-wrap gap-3">
                  <UFormField :label="t('guardian.access.roles.fields.name')" required>
                    <UInput v-model="form.name" class="w-56" data-testid="guardian-role-new-name" />
                  </UFormField>
                  <UFormField :label="t('guardian.access.roles.fields.slug')" :hint="t('guardian.access.roles.slug_hint')">
                    <UInput v-model="form.slug" class="w-48" data-testid="guardian-role-new-slug" />
                  </UFormField>
                  <UFormField :label="t('guardian.access.roles.fields.scope')">
                    <USelect v-model="form.scope" :items="scopes" class="w-48" data-testid="guardian-role-new-scope" />
                  </UFormField>
                </div>
                <UFormField :label="t('guardian.access.roles.fields.description')">
                  <UTextarea v-model="form.description" :rows="2" class="w-full" data-testid="guardian-role-new-description" />
                </UFormField>
                <GuardianRolePermissions v-model="form.permissions" :permissions="permissions" id-prefix="guardian-role-new" />
                <div>
                  <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="guardian-role-new-submit">{{ t('guardian.access.roles.create') }}</UButton>
                </div>
              </form>
            </UCard>
          </div>
        </GuardianState>
      </AtriumIntegrationScreen>
    </template>
  </UDashboardPanel>
</template>
