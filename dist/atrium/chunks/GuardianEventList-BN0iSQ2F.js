import { t as e } from "./GuardianState-DytBr9ih.js";
import { f as t, i as n, o as r } from "./guardian-BXKFb2X7.js";
import { o as i } from "./admin-_cLX5Zre.js";
import { AtriumState as a } from "@refactor-circus/atrium";
import { computed as o, createBlock as s, createCommentVNode as c, createElementVNode as l, createTextVNode as u, createVNode as d, defineComponent as f, openBlock as p, ref as m, toDisplayString as h, unref as g, watch as _, withCtx as v } from "vue";
import { UButton as y, ULink as b, UTable as x } from "@refactor-circus/atrium/ui";
import { useI18n as S } from "vue-i18n";
// src/components/admin/GuardianEventList.vue?vue&type=script&setup=true&lang.ts
var C = ["data-testid"], w = ["data-testid"], T = { class: "flex gap-2" }, E = /* @__PURE__ */ f({
	__name: "GuardianEventList",
	props: {
		filters: {},
		testid: {}
	},
	setup(f) {
		let E = f, { t: D } = S(), O = m(null), k = m([]), A = o(() => ({
			...Object.fromEntries(Object.entries(E.filters).filter(([, e]) => e !== null && e !== "")),
			per_page: 50,
			...O.value ? { cursor: O.value } : {}
		}));
		_(() => E.filters, () => {
			O.value = null, k.value = [];
		}, { deep: !0 });
		let { data: j, pending: M, failed: N, status: P } = t(async (e) => r(await e.GET("/security-events", { params: { query: A.value } })), A), F = o(() => j.value?.data ?? []), I = o(() => [
			{
				accessorKey: "type",
				header: D("guardian.admin.events.fields.type")
			},
			{
				accessorKey: "user_id",
				header: D("guardian.admin.events.fields.user")
			},
			{
				accessorKey: "ip",
				header: D("guardian.admin.events.fields.ip"),
				meta: { class: { td: "text-muted" } }
			},
			{
				accessorKey: "occurred_at",
				header: D("guardian.admin.events.fields.occurred_at"),
				meta: { class: { td: "text-muted" } }
			}
		]);
		function L() {
			let e = j.value?.meta?.next_cursor;
			e && (k.value = [...k.value, O.value], O.value = e);
		}
		function R() {
			O.value = k.value.at(-1) ?? null, k.value = k.value.slice(0, -1);
		}
		return (t, r) => {
			let o = b, m = a, _ = x, S = y;
			return p(), s(e, {
				ready: !g(M) && g(j) !== null,
				failed: g(N),
				status: g(P)
			}, {
				default: v(() => [l("div", {
					class: "space-y-3",
					"data-testid": f.testid
				}, [d(_, {
					data: g(F),
					columns: g(I)
				}, {
					"type-cell": v(({ row: e }) => [l("div", { "data-testid": `guardian-event-${e.original.id}` }, [d(o, {
						to: g(i)(e.original.id),
						class: "font-mono hover:underline",
						"data-testid": "guardian-event-link"
					}, {
						default: v(() => [u(h(e.original.type), 1)]),
						_: 2
					}, 1032, ["to"])], 8, w)]),
					"user_id-cell": v(({ row: e }) => [u(h(e.original.user_id ?? "—"), 1)]),
					"ip-cell": v(({ row: e }) => [u(h(e.original.ip ?? "—"), 1)]),
					"occurred_at-cell": v(({ row: e }) => [u(h(g(n)(e.original.occurred_at)), 1)]),
					empty: v(() => [d(m, {
						compact: "",
						title: g(D)("guardian.admin.events.empty"),
						"data-testid": `${f.testid}-empty`
					}, null, 8, ["title", "data-testid"])]),
					_: 1
				}, 8, ["data", "columns"]), l("div", T, [g(k).length > 0 ? (p(), s(S, {
					key: 0,
					size: "xs",
					color: "neutral",
					variant: "soft",
					"data-testid": `${f.testid}-newer`,
					onClick: R
				}, {
					default: v(() => [u(h(g(D)("guardian.admin.events.newer")), 1)]),
					_: 1
				}, 8, ["data-testid"])) : c("", !0), g(j)?.meta?.next_cursor ? (p(), s(S, {
					key: 1,
					size: "xs",
					color: "neutral",
					variant: "soft",
					"data-testid": `${f.testid}-older`,
					onClick: L
				}, {
					default: v(() => [u(h(g(D)("guardian.admin.events.older")), 1)]),
					_: 1
				}, 8, ["data-testid"])) : c("", !0)])], 8, C)]),
				_: 1
			}, 8, [
				"ready",
				"failed",
				"status"
			]);
		};
	}
});
export { E as t };
