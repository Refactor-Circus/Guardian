import { i as e } from "./admin-_cLX5Zre.js";
import { u as t } from "./access-Ea_ryMId.js";
import { t as n } from "./GuardianState-DytBr9ih.js";
import { f as r, i, o as a } from "./guardian-BXKFb2X7.js";
import { AtriumDetails as o, AtriumIntegrationScreen as s, AtriumState as c, useHead as l } from "@refactor-circus/atrium";
import { computed as u, createBlock as d, createCommentVNode as f, createElementBlock as p, createElementVNode as m, createVNode as h, defineComponent as g, openBlock as _, toDisplayString as v, unref as y, withCtx as b } from "vue";
import { UButton as x, UCard as S, UDashboardNavbar as C, UDashboardPanel as w } from "@refactor-circus/atrium/ui";
import { useI18n as T } from "vue-i18n";
import { useRoute as E } from "vue-router";
// src/pages/security-events/[event].vue?vue&type=script&setup=true&lang.ts
var D = {
	key: 0,
	class: "space-y-4",
	"data-testid": "guardian-security-event"
}, O = { class: "font-semibold" }, k = /* @__PURE__ */ g({
	__name: "[event]",
	setup(g) {
		let { t: k } = T(), A = E(), j = u(() => String(A.params.event)), { data: M, pending: N, failed: P, status: F } = r(async (e) => a(await e.GET("/security-events/{event}", { params: { path: { event: j.value } } })).data, j), I = u(() => Object.entries(M.value?.context ?? {})), L = u(() => {
			let e = M.value;
			return e ? [
				{
					label: k("guardian.admin.events.fields.type"),
					value: e.type,
					mono: !0,
					testid: "guardian-event-type"
				},
				{
					label: k("guardian.admin.events.fields.user"),
					value: e.user_id,
					href: e.user_id ? t(e.user_id) : null,
					testid: "guardian-event-user"
				},
				{
					label: k("guardian.admin.events.fields.ip"),
					value: e.ip
				},
				{
					label: k("guardian.admin.event.user_agent"),
					value: e.user_agent
				},
				{
					label: k("guardian.admin.event.identifier_hash"),
					value: e.identifier_hash,
					mono: !0
				},
				{
					label: k("guardian.admin.events.fields.occurred_at"),
					value: i(e.occurred_at)
				}
			] : [];
		}), R = u(() => I.value.map(([t, n]) => ({
			label: t,
			value: e(n),
			mono: !0,
			testid: `guardian-event-context-${t}`
		})));
		return l({ title: () => M.value?.type ?? k("guardian.admin.event.title") }), (e, t) => {
			let r = x, i = C, a = o, l = S, u = c, g = s, T = w;
			return _(), d(T, null, {
				header: b(() => [h(i, { title: y(M)?.type ?? y(k)("guardian.admin.event.title") }, {
					leading: b(() => [h(r, {
						to: "/guardian/security-events",
						variant: "ghost",
						icon: "i-lucide-arrow-left",
						"aria-label": y(k)("guardian.admin.events.title"),
						"data-testid": "guardian-event-back"
					}, null, 8, ["aria-label"])]),
					_: 1
				}, 8, ["title"])]),
				body: b(() => [h(g, {
					"screen-key": "guardian.security-event",
					record: y(j)
				}, {
					default: b(() => [h(n, {
						ready: !y(N) && y(M) !== null,
						failed: y(P),
						status: y(F),
						"not-found": y(k)("guardian.admin.event.not_found")
					}, {
						default: b(() => [y(M) ? (_(), p("div", D, [h(l, null, {
							default: b(() => [h(a, { items: y(L) }, null, 8, ["items"])]),
							_: 1
						}), h(l, null, {
							header: b(() => [m("h2", O, v(y(k)("guardian.admin.event.context")), 1)]),
							default: b(() => [y(I).length === 0 ? (_(), d(u, {
								key: 0,
								compact: "",
								title: y(k)("guardian.admin.event.no_context"),
								"data-testid": "guardian-event-context-empty"
							}, null, 8, ["title"])) : (_(), d(a, {
								key: 1,
								items: y(R),
								"data-testid": "guardian-event-context"
							}, null, 8, ["items"]))]),
							_: 1
						})])) : f("", !0)]),
						_: 1
					}, 8, [
						"ready",
						"failed",
						"status",
						"not-found"
					])]),
					_: 1
				}, 8, ["record"])]),
				_: 1
			});
		};
	}
});
export { k as default };
