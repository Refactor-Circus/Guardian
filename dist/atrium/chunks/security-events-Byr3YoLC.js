import { t as e } from "./GuardianEventList-DlZYO5Zo.js";
import { a as t, c as n, r } from "./admin-_cLX5Zre.js";
import { n as i } from "./access-CMUXpA7X.js";
import { i as a, n as o, t as s } from "./ui-BHDIeg3q.js";
import { AtriumIntegrationScreen as c, useHead as l } from "@refactor-circus/atrium";
import { computed as u, createBlock as d, createElementVNode as f, createTextVNode as p, createVNode as m, defineComponent as h, openBlock as g, reactive as _, ref as v, toDisplayString as y, unref as b, withCtx as x, withModifiers as S } from "vue";
import { UButton as C, UDashboardNavbar as w, UDashboardPanel as T, UFormField as E, UInput as D, USelect as O } from "@refactor-circus/atrium/ui";
import { useI18n as k } from "vue-i18n";
// src/pages/security-events/index.vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "guardian-security-events"
}, j = /* @__PURE__ */ h({
	__name: "index",
	setup(h) {
		let { t: j } = k();
		l({ title: () => j("guardian.admin.events.title") });
		let M = u(() => [{
			label: j("guardian.admin.events.any_type"),
			value: s
		}, ...r.map((e) => ({
			label: e,
			value: e
		}))]), N = _({
			type: "",
			user: "",
			from: "",
			to: ""
		}), P = v({});
		function F() {
			P.value = {
				type: i(N.type),
				user: i(N.user),
				from: n(N.from),
				to: t(N.to)
			};
		}
		function I() {
			Object.assign(N, {
				type: "",
				user: "",
				from: "",
				to: ""
			}), F();
		}
		return (t, n) => {
			let r = w, i = O, s = E, l = D, u = C, h = c, _ = T;
			return g(), d(_, null, {
				header: x(() => [m(r, { title: b(j)("guardian.admin.events.title") }, null, 8, ["title"])]),
				body: x(() => [m(h, { "screen-key": "guardian.security-events" }, {
					default: x(() => [f("div", A, [f("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "guardian-events-filters",
						onSubmit: S(F, ["prevent"])
					}, [
						m(s, { label: b(j)("guardian.admin.events.fields.type") }, {
							default: x(() => [m(i, {
								"model-value": b(o)(b(N).type),
								items: b(M),
								class: "w-64",
								"data-testid": "guardian-events-filter-type",
								"onUpdate:modelValue": n[0] ||= (e) => b(N).type = b(a)(e)
							}, null, 8, ["model-value", "items"])]),
							_: 1
						}, 8, ["label"]),
						m(s, { label: b(j)("guardian.admin.events.fields.user") }, {
							default: x(() => [m(l, {
								modelValue: b(N).user,
								"onUpdate:modelValue": n[1] ||= (e) => b(N).user = e,
								class: "w-40",
								"data-testid": "guardian-events-filter-user"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						m(s, { label: b(j)("guardian.admin.events.from") }, {
							default: x(() => [m(l, {
								modelValue: b(N).from,
								"onUpdate:modelValue": n[2] ||= (e) => b(N).from = e,
								type: "date",
								"data-testid": "guardian-events-filter-from"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						m(s, { label: b(j)("guardian.admin.events.to") }, {
							default: x(() => [m(l, {
								modelValue: b(N).to,
								"onUpdate:modelValue": n[3] ||= (e) => b(N).to = e,
								type: "date",
								"data-testid": "guardian-events-filter-to"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						m(u, {
							type: "submit",
							icon: "i-lucide-filter",
							"data-testid": "guardian-events-filter"
						}, {
							default: x(() => [p(y(b(j)("guardian.admin.filter")), 1)]),
							_: 1
						}),
						m(u, {
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": "guardian-events-clear",
							onClick: I
						}, {
							default: x(() => [p(y(b(j)("guardian.admin.clear")), 1)]),
							_: 1
						})
					], 32), m(e, {
						filters: b(P),
						testid: "guardian-events"
					}, null, 8, ["filters"])])]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { j as default };
