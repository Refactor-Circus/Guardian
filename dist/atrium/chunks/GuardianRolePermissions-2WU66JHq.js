import { c as e } from "./guardian-BXKFb2X7.js";
import { Fragment as t, createBlock as n, createCommentVNode as r, createElementBlock as i, createElementVNode as a, defineComponent as o, mergeModels as s, openBlock as c, renderList as l, toDisplayString as u, unref as d, useModel as f, withCtx as p } from "vue";
import { UCheckbox as m } from "@refactor-circus/atrium/ui";
import { useI18n as h } from "vue-i18n";
// src/components/access/GuardianRolePermissions.vue?vue&type=script&setup=true&lang.ts
var g = { class: "space-y-2" }, _ = { class: "mb-1 text-sm font-medium" }, v = {
	key: 0,
	class: "text-sm text-muted"
}, y = {
	key: 1,
	class: "grid gap-2 sm:grid-cols-2 lg:grid-cols-3"
}, b = {
	key: 2,
	class: "text-sm text-error"
}, x = /* @__PURE__ */ o({
	__name: "GuardianRolePermissions",
	props: /*@__PURE__*/ s({
		permissions: {},
		idPrefix: {},
		error: {}
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(o) {
		let s = o, x = f(o, "modelValue"), { t: S } = h();
		return (o, f) => {
			let h = m;
			return c(), i("fieldset", g, [
				a("legend", _, u(d(S)("guardian.access.roles.fields.permissions")), 1),
				s.permissions.length === 0 ? (c(), i("p", v, u(d(S)("guardian.access.roles.no_permissions")), 1)) : (c(), i("div", y, [(c(!0), i(t, null, l(s.permissions, (t) => (c(), n(h, {
					key: t.name,
					"model-value": x.value.includes(t.name),
					"data-testid": `${s.idPrefix}-permission-${t.name}`,
					"onUpdate:modelValue": (n) => x.value = d(e)(x.value, t.name, n)
				}, {
					label: p(() => [a("code", null, u(t.name), 1)]),
					_: 2
				}, 1032, [
					"model-value",
					"data-testid",
					"onUpdate:modelValue"
				]))), 128))])),
				s.error ? (c(), i("p", b, u(s.error), 1)) : r("", !0)
			]);
		};
	}
});
export { x as t };
