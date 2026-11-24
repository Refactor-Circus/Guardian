import { t as e } from "./GuardianState-DytBr9ih.js";
import { f as t, o as n } from "./guardian-BXKFb2X7.js";
import { AtriumDetails as r, AtriumIntegrationScreen as i, useHead as a } from "@refactor-circus/atrium";
import { computed as o, createBlock as s, createCommentVNode as c, createElementVNode as l, createVNode as u, defineComponent as d, openBlock as f, toDisplayString as p, unref as m, withCtx as h } from "vue";
import { UCard as g, UDashboardNavbar as _, UDashboardPanel as v } from "@refactor-circus/atrium/ui";
import { useI18n as y } from "vue-i18n";
// src/pages/password-policy/index.vue?vue&type=script&setup=true&lang.ts
var b = { class: "text-sm text-muted" }, x = /* @__PURE__ */ d({
	__name: "index",
	setup(d) {
		let { t: x } = y();
		a({ title: () => x("guardian.admin.password_policy.title") });
		let { data: S, pending: C, failed: w, status: T } = t(async (e) => n(await e.GET("/password-policy")).data), E = o(() => {
			let e = S.value;
			return e ? [
				{
					label: x("guardian.admin.password_policy.length"),
					value: x("guardian.admin.password_policy.length_value", {
						min: e.min_length,
						max: e.max_length
					}),
					testid: "guardian-password-length"
				},
				...[
					"letters",
					"mixed_case",
					"numbers",
					"symbols"
				].map((t) => ({
					label: x(`guardian.admin.password_policy.rules.${t}`),
					value: D(e[t]),
					testid: `guardian-password-${t}`
				})),
				{
					label: x("guardian.admin.password_policy.uncompromised"),
					value: e.uncompromised ? x("guardian.admin.password_policy.uncompromised_on", { threshold: e.uncompromised_threshold }) : x("guardian.admin.no"),
					testid: "guardian-password-uncompromised"
				},
				{
					label: x("guardian.admin.password_policy.history"),
					value: e.history > 0 ? x("guardian.admin.password_policy.history_value", { count: e.history }) : x("guardian.admin.no"),
					testid: "guardian-password-history"
				},
				{
					label: x("guardian.admin.password_policy.max_age"),
					value: e.max_age_days ? x("guardian.admin.password_policy.max_age_value", { days: e.max_age_days }) : x("guardian.admin.never"),
					testid: "guardian-password-max-age"
				}
			] : [];
		});
		function D(e) {
			return x(e ? "guardian.admin.yes" : "guardian.admin.no");
		}
		return (t, n) => {
			let a = _, o = r, d = g, y = i, D = v;
			return f(), s(D, null, {
				header: h(() => [u(a, { title: m(x)("guardian.admin.password_policy.title") }, null, 8, ["title"])]),
				body: h(() => [u(y, { "screen-key": "guardian.password-policy" }, {
					default: h(() => [u(e, {
						ready: !m(C) && m(S) !== null,
						failed: m(w),
						status: m(T)
					}, {
						default: h(() => [m(S) ? (f(), s(d, {
							key: 0,
							"data-testid": "guardian-password-policy"
						}, {
							header: h(() => [l("p", b, p(m(x)("guardian.admin.password_policy.read_only")), 1)]),
							default: h(() => [u(o, { items: m(E) }, null, 8, ["items"])]),
							_: 1
						})) : c("", !0)]),
						_: 1
					}, 8, [
						"ready",
						"failed",
						"status"
					])]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { x as default };
