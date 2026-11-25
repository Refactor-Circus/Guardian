import { t as e } from "./GuardianErrors-mhgGBfyk.js";
import { t } from "./GuardianEventList-BN0iSQ2F.js";
import { t as n } from "./admin-_cLX5Zre.js";
import { d as r, l as i, o as a, u as o } from "./guardian-BXKFb2X7.js";
import { AtriumIntegrationScreen as s, useHead as c } from "@refactor-circus/atrium";
import { createBlock as l, createCommentVNode as u, createElementVNode as d, createTextVNode as f, createVNode as p, defineComponent as m, isRef as h, openBlock as g, ref as _, toDisplayString as v, unref as y, withCtx as b, withModifiers as x } from "vue";
import { UAlert as S, UButton as C, UCard as w, UDashboardNavbar as T, UDashboardPanel as E, UFormField as D, UInput as O } from "@refactor-circus/atrium/ui";
import { useI18n as k } from "vue-i18n";
// src/pages/lockouts/index.vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "guardian-lockouts"
}, j = { class: "min-w-0" }, M = { class: "font-semibold" }, N = { class: "mt-1 text-sm text-muted" }, P = { class: "mt-3" }, F = { class: "font-semibold" }, I = /* @__PURE__ */ m({
	__name: "index",
	setup(m) {
		let { t: I } = k(), L = o(), R = r();
		c({ title: () => I("guardian.admin.lockouts.title") });
		let z = _(""), B = _(null), V = { type: n };
		async function H() {
			let e = z.value.trim();
			B.value = null, L && e !== "" && window.confirm(I("guardian.admin.lockouts.confirm", { identifier: e })) && await R.run(async () => a(await L.DELETE("/lockouts", i({ body: { identifier: e } })))) && (B.value = e, z.value = "");
		}
		return (n, r) => {
			let i = T, a = O, o = D, c = C, m = S, _ = w, k = s, L = E;
			return g(), l(L, null, {
				header: b(() => [p(i, { title: y(I)("guardian.admin.lockouts.title") }, null, 8, ["title"])]),
				body: b(() => [p(k, { "screen-key": "guardian.lockouts" }, {
					default: b(() => [d("div", A, [p(_, null, {
						header: b(() => [d("div", j, [d("h2", M, v(y(I)("guardian.admin.lockouts.clear")), 1), d("p", N, v(y(I)("guardian.admin.lockouts.hint")), 1)])]),
						default: b(() => [
							d("form", {
								class: "flex flex-wrap items-start gap-3",
								"data-testid": "guardian-lockout-form",
								onSubmit: x(H, ["prevent"])
							}, [p(o, {
								label: y(I)("guardian.admin.lockouts.identifier"),
								error: y(R).error("identifier"),
								required: ""
							}, {
								default: b(() => [p(a, {
									modelValue: y(z),
									"onUpdate:modelValue": r[0] ||= (e) => h(z) ? z.value = e : null,
									class: "w-72",
									autocomplete: "off",
									"data-testid": "guardian-lockout-identifier"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]), p(c, {
								type: "submit",
								icon: "i-lucide-lock-open",
								loading: y(R).busy.value,
								class: "mt-field-label",
								"data-testid": "guardian-lockout-submit"
							}, {
								default: b(() => [f(v(y(I)("guardian.admin.lockouts.submit")), 1)]),
								_: 1
							}, 8, ["loading"])], 32),
							y(B) ? (g(), l(m, {
								key: 0,
								class: "mt-3",
								color: "success",
								variant: "subtle",
								title: y(I)("guardian.admin.lockouts.cleared", { identifier: y(B) }),
								"data-testid": "guardian-lockout-cleared"
							}, null, 8, ["title"])) : u("", !0),
							d("div", P, [y(R).errors.value.identifier === void 0 ? (g(), l(e, {
								key: 0,
								action: y(R),
								testid: "guardian-lockout"
							}, null, 8, ["action"])) : u("", !0)])
						]),
						_: 1
					}), p(_, null, {
						header: b(() => [d("h2", F, v(y(I)("guardian.admin.lockouts.recent")), 1)]),
						default: b(() => [p(t, {
							filters: V,
							testid: "guardian-lockout-events"
						})]),
						_: 1
					})])]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { I as default };
