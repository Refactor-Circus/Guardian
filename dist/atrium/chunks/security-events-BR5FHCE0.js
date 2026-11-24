import { t as e } from "./GuardianEventList-BN0iSQ2F.js";
import { a as t, c as n, r } from "./admin-_cLX5Zre.js";
import { n as i } from "./access-Ea_ryMId.js";
import { AtriumIntegrationScreen as a, AtriumSelect as o, useHead as s } from "@refactor-circus/atrium";
import { createBlock as c, createElementVNode as l, createTextVNode as u, createVNode as d, defineComponent as f, openBlock as p, reactive as m, ref as h, toDisplayString as g, unref as _, withCtx as v, withModifiers as y } from "vue";
import { UButton as b, UDashboardNavbar as x, UDashboardPanel as S, UFormField as C, UInput as w } from "@refactor-circus/atrium/ui";
import { useI18n as T } from "vue-i18n";
// src/pages/security-events/index.vue?vue&type=script&setup=true&lang.ts
var E = {
	class: "space-y-4",
	"data-testid": "guardian-security-events"
}, D = /* @__PURE__ */ f({
	__name: "index",
	setup(f) {
		let { t: D } = T();
		s({ title: () => D("guardian.admin.events.title") });
		let O = r.map((e) => ({
			label: e,
			value: e
		})), k = m({
			type: "",
			user: "",
			from: "",
			to: ""
		}), A = h({});
		function j() {
			A.value = {
				type: i(k.type),
				user: i(k.user),
				from: n(k.from),
				to: t(k.to)
			};
		}
		function M() {
			Object.assign(k, {
				type: "",
				user: "",
				from: "",
				to: ""
			}), j();
		}
		return (t, n) => {
			let r = x, i = o, s = C, f = w, m = b, h = a, T = S;
			return p(), c(T, null, {
				header: v(() => [d(r, { title: _(D)("guardian.admin.events.title") }, null, 8, ["title"])]),
				body: v(() => [d(h, { "screen-key": "guardian.security-events" }, {
					default: v(() => [l("div", E, [l("form", {
						class: "flex flex-wrap items-start gap-3",
						"data-testid": "guardian-events-filters",
						onSubmit: y(j, ["prevent"])
					}, [
						d(s, { label: _(D)("guardian.admin.events.fields.type") }, {
							default: v(() => [d(i, {
								modelValue: _(k).type,
								"onUpdate:modelValue": n[0] ||= (e) => _(k).type = e,
								items: _(O),
								blank: _(D)("guardian.admin.events.any_type"),
								class: "w-64",
								"data-testid": "guardian-events-filter-type"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]),
						d(s, { label: _(D)("guardian.admin.events.fields.user") }, {
							default: v(() => [d(f, {
								modelValue: _(k).user,
								"onUpdate:modelValue": n[1] ||= (e) => _(k).user = e,
								class: "w-40",
								"data-testid": "guardian-events-filter-user"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						d(s, { label: _(D)("guardian.admin.events.from") }, {
							default: v(() => [d(f, {
								modelValue: _(k).from,
								"onUpdate:modelValue": n[2] ||= (e) => _(k).from = e,
								type: "date",
								"data-testid": "guardian-events-filter-from"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						d(s, { label: _(D)("guardian.admin.events.to") }, {
							default: v(() => [d(f, {
								modelValue: _(k).to,
								"onUpdate:modelValue": n[3] ||= (e) => _(k).to = e,
								type: "date",
								"data-testid": "guardian-events-filter-to"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						d(m, {
							type: "submit",
							icon: "i-lucide-filter",
							class: "mt-field-label",
							"data-testid": "guardian-events-filter"
						}, {
							default: v(() => [u(g(_(D)("guardian.admin.filter")), 1)]),
							_: 1
						}),
						d(m, {
							variant: "ghost",
							icon: "i-lucide-x",
							class: "mt-field-label",
							"data-testid": "guardian-events-clear",
							onClick: M
						}, {
							default: v(() => [u(g(_(D)("guardian.admin.clear")), 1)]),
							_: 1
						})
					], 32), d(e, {
						filters: _(A),
						testid: "guardian-events"
					}, null, 8, ["filters"])])]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { D as default };
