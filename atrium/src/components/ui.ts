/*
 * Small helpers for the Nuxt UI fields Guardian's forms compose.
 *
 * A <USelect> item cannot hold '' (Reka UI keeps it for "no selection"), so
 * an option meaning "none" (global scope, any type, no connection) is the
 * `NONE` item, while the form keeps '' as before: bind the select with
 * `:model-value="choice(form.x)"` and `@update:model-value="form.x = unchoice($event)"`.
 */

/** The select value standing for a blank form value. */
export const NONE = '__none'

/** A select item: a label and its value. */
export interface Choice {
  label: string
  value: string
}

/** The select value of a form value: `NONE` for ''. */
export function choice(value: string): string {
  return value === '' ? NONE : value
}

/** The form value of a select value: '' for `NONE`. */
export function unchoice(value: unknown): string {
  const text = value === null || value === undefined ? '' : String(value)

  return text === NONE ? '' : text
}

/** `list` with `value` in it when `on`, without it otherwise; for a checkbox per value of a list. */
export function toggled<T>(list: readonly T[], value: T, on: boolean | 'indeterminate'): T[] {
  return on === true ? [...new Set([...list, value])] : list.filter(candidate => candidate !== value)
}
