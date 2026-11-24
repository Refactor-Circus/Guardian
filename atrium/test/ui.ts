import { DOMWrapper, type VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import { USelect } from '@refactor-circus/atrium/ui'
import { unchoice, type Choice } from '../src/components/ui'

/*
 * Driving Guardian's Nuxt UI fields in tests (D-091): a <USelect> is a Reka
 * listbox, not a native <select>, a <UCheckbox> / <USwitch> a
 * `role="checkbox"` / `role="switch"` button, and a <UTable> row carries no
 * attributes of its own, so each row marks one cell with its test id.
 */

type Root = VueWrapper | DOMWrapper<Element>

/** The items a <USelect> offers. */
function itemsOf(select: VueWrapper): Choice[] {
  return (select.props() as { items?: Choice[] }).items ?? []
}

function selectOf(wrapper: VueWrapper, testid: string): VueWrapper {
  const select = wrapper.findAllComponents(USelect).find(candidate => candidate.find(`[data-testid="${testid}"]`).exists())

  if (!select) {
    throw new Error(`No <USelect> with data-testid "${testid}"`)
  }

  return select
}

/** Picks `value` in the <USelect> marked `testid`, as choosing that item does. */
export async function choose(wrapper: VueWrapper, testid: string, value: string): Promise<void> {
  const select = selectOf(wrapper, testid)
  const items = itemsOf(select)
  const item = items.find(candidate => unchoice(candidate.value) === value)

  if (!item) {
    throw new Error(`<USelect> "${testid}" offers no "${value}"`)
  }

  select.vm.$emit('update:modelValue', item.value)
  await nextTick()
}

/** The values the <USelect> marked `testid` offers, '' for its "none" item. */
export function choices(wrapper: VueWrapper, testid: string): string[] {
  return itemsOf(selectOf(wrapper, testid)).map(item => unchoice(item.value))
}

/** Whether the <UCheckbox> or <USwitch> marked `testid` is on. */
export function isChecked(root: Root, testid: string): boolean {
  return root.find(`[data-testid="${testid}"]`).attributes('aria-checked') === 'true'
}

/** Turns the <UCheckbox> or <USwitch> marked `testid` on or off, by clicking it when it differs. */
export async function check(root: Root, testid: string, on: boolean): Promise<void> {
  const field = root.find(`[data-testid="${testid}"]`)

  if (!field.exists()) {
    throw new Error(`No checkbox with data-testid "${testid}"`)
  }

  if ((field.attributes('aria-checked') === 'true') !== on) {
    await field.trigger('click')
  }
}

/** The <UTable> row holding the cell marked `testid`. */
export function rowOf(root: Root, testid: string): DOMWrapper<Element> {
  const marker = root.find(`[data-testid="${testid}"]`)
  const tr = marker.exists() ? marker.element.closest('tr') : null

  return tr ? new DOMWrapper(tr) : marker
}
