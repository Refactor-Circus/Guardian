import { i as e } from "./admin-_cLX5Zre.js";
import { u as t } from "./access-CMUXpA7X.js";
import { t as n } from "./GuardianState-DytBr9ih.js";
import { d as r, i, o as a } from "./guardian-D9MeowUD.js";
import { AtriumIntegrationScreen as o, AtriumState as s, useHead as c } from "@refactor-circus/atrium";
import { Fragment as l, computed as u, createBlock as d, createCommentVNode as f, createElementBlock as p, createElementVNode as m, createTextVNode as h, createVNode as g, defineComponent as _, openBlock as v, renderList as y, toDisplayString as b, unref as x, withCtx as S } from "vue";
import { UButton as C, UCard as w, UDashboardNavbar as T, UDashboardPanel as E, ULink as D } from "@refactor-circus/atrium/ui";
import { useI18n as O } from "vue-i18n";
import { useRoute as k } from "vue-router";
// src/pages/security-events/[event].vue?vue&type=script&setup=true&lang.ts
var A = {
	key: 0,
	class: "space-y-4",
	"data-testid": "guardian-security-event"
}, j = { class: "grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[12rem_1fr]" }, M = { class: "text-muted" }, N = {
	class: "font-mono",
	"data-testid": "guardian-event-type"
}, P = { class: "text-muted" }, F = { "data-testid": "guardian-event-user" }, I = { key: 1 }, L = { class: "text-muted" }, R = { class: "text-muted" }, ee = { class: "break-all" }, z = { class: "text-muted" }, B = { class: "break-all font-mono text-xs" }, V = { class: "text-muted" }, H = { class: "font-semibold" }, U = {
	key: 1,
	class: "grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[12rem_1fr]",
	"data-testid": "guardian-event-context"
}, W = { class: "font-mono text-muted" }, G = ["data-testid"], K = /* @__PURE__ */ _({
	__name: "[event]",
	setup(_) {
		let { t: K } = O(), q = k(), J = u(() => String(q.params.event)), { data: Y, pending: X, failed: Z, status: Q } = r(async (e) => a(await e.GET("/security-events/{event}", { params: { path: { event: J.value } } })).data, J), $ = u(() => Object.entries(Y.value?.context ?? {}));
		return c({ title: () => Y.value?.type ?? K("guardian.admin.event.title") }), (r, a) => {
			let c = C, u = T, _ = D, O = w, k = s, q = o, te = E;
			return v(), d(te, null, {
				header: S(() => [g(u, { title: x(Y)?.type ?? x(K)("guardian.admin.event.title") }, {
					leading: S(() => [g(c, {
						to: "/guardian/security-events",
						variant: "ghost",
						icon: "i-lucide-arrow-left",
						"aria-label": x(K)("guardian.admin.events.title"),
						"data-testid": "guardian-event-back"
					}, null, 8, ["aria-label"])]),
					_: 1
				}, 8, ["title"])]),
				body: S(() => [g(q, {
					"screen-key": "guardian.security-event",
					record: x(J)
				}, {
					default: S(() => [g(n, {
						ready: !x(X) && x(Y) !== null,
						failed: x(Z),
						status: x(Q),
						"not-found": x(K)("guardian.admin.event.not_found")
					}, {
						default: S(() => [x(Y) ? (v(), p("div", A, [g(O, null, {
							default: S(() => [m("dl", j, [
								m("dt", M, b(x(K)("guardian.admin.events.fields.type")), 1),
								m("dd", N, b(x(Y).type), 1),
								m("dt", P, b(x(K)("guardian.admin.events.fields.user")), 1),
								m("dd", F, [x(Y).user_id ? (v(), d(_, {
									key: 0,
									to: x(t)(x(Y).user_id),
									class: "hover:underline"
								}, {
									default: S(() => [h(b(x(Y).user_id), 1)]),
									_: 1
								}, 8, ["to"])) : (v(), p("span", I, "—"))]),
								m("dt", L, b(x(K)("guardian.admin.events.fields.ip")), 1),
								m("dd", null, b(x(Y).ip ?? "—"), 1),
								m("dt", R, b(x(K)("guardian.admin.event.user_agent")), 1),
								m("dd", ee, b(x(Y).user_agent ?? "—"), 1),
								m("dt", z, b(x(K)("guardian.admin.event.identifier_hash")), 1),
								m("dd", B, b(x(Y).identifier_hash ?? "—"), 1),
								m("dt", V, b(x(K)("guardian.admin.events.fields.occurred_at")), 1),
								m("dd", null, b(x(i)(x(Y).occurred_at)), 1)
							])]),
							_: 1
						}), g(O, null, {
							header: S(() => [m("h2", H, b(x(K)("guardian.admin.event.context")), 1)]),
							default: S(() => [x($).length === 0 ? (v(), d(k, {
								key: 0,
								compact: "",
								title: x(K)("guardian.admin.event.no_context"),
								"data-testid": "guardian-event-context-empty"
							}, null, 8, ["title"])) : (v(), p("dl", U, [(v(!0), p(l, null, y(x($), ([t, n]) => (v(), p(l, { key: t }, [m("dt", W, b(t), 1), m("dd", {
								class: "break-all",
								"data-testid": `guardian-event-context-${t}`
							}, b(x(e)(n)), 9, G)], 64))), 128))]))]),
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
export { K as default };
