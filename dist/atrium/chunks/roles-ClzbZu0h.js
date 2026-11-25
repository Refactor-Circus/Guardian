import { t as e } from "./GuardianErrors-mhgGBfyk.js";
import { t } from "./GuardianPager-DnfHQ8Kk.js";
import { t as ee } from "./GuardianRolePermissions-2WU66JHq.js";
import { a as n, c as te, i as r, n as i, o as a, s as o, t as s } from "./access-Ea_ryMId.js";
import { t as ne } from "./GuardianState-DytBr9ih.js";
import { d as c, f as l, o as u } from "./guardian-BXKFb2X7.js";
import { AtriumIntegrationScreen as d, AtriumState as f, useAtriumScreen as p, useHead as m } from "@refactor-circus/atrium";
import { computed as h, createBlock as g, createCommentVNode as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, openBlock as S, reactive as C, ref as w, toDisplayString as T, unref as E, withCtx as D, withModifiers as O } from "vue";
import { UBadge as re, UButton as ie, UCard as ae, UDashboardNavbar as k, UDashboardPanel as A, UFormField as j, UInput as M, ULink as N, USelect as P, UTable as F, UTextarea as I } from "@refactor-circus/atrium/ui";
import { useI18n as L } from "vue-i18n";
import { useRouter as R } from "vue-router";
// src/pages/roles/index.vue?vue&type=script&setup=true&lang.ts
var z = {
	class: "space-y-4",
	"data-testid": "guardian-roles"
}, B = ["data-testid"], V = { class: "font-medium" }, H = { class: "flex flex-wrap gap-3" }, U = /* @__PURE__ */ x({
	__name: "index",
	setup(x) {
		let { t: U } = L(), W = R(), G = c();
		m({ title: () => U("guardian.access.roles.title") });
		let { data: K } = p("guardian.roles"), q = h(() => o(K.value?.data.options)), J = w(1), Y = h(() => ({
			per_page: 25,
			...J.value > 1 ? { page: J.value } : {}
		})), { client: X, data: Z, pending: oe, failed: se, status: ce } = l(async (e) => u(await e.GET("/roles", { params: { query: Y.value } })), Y), le = te(X), ue = h(() => Z.value?.data ?? []), Q = h(() => r(Z.value)), de = h(() => [
			{
				id: "name",
				header: U("guardian.access.roles.fields.name")
			},
			{
				id: "scope",
				header: U("guardian.access.roles.fields.scope")
			},
			{
				id: "permissions",
				header: U("guardian.access.roles.fields.permissions"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			}
		]), fe = h(() => [...s.map((e) => ({
			label: U(`guardian.access.scopes.${e}`),
			value: e
		})), ...q.value.map((e) => ({
			label: e.key,
			value: e.type
		}))]), $ = C({
			name: "",
			slug: "",
			scope: "global",
			description: "",
			permissions: []
		});
		function pe(e) {
			return s.includes(e) ? U(`guardian.access.scopes.${e}`) : a(e, q.value) ?? e;
		}
		function me(e) {
			J.value = e;
		}
		async function he() {
			if (!X) return;
			let e = [];
			await G.run(async () => {
				e.push(u(await X.POST("/roles", { body: {
					name: $.name.trim(),
					slug: i($.slug),
					scope: $.scope,
					description: i($.description),
					permissions: $.permissions
				} })).data);
			}) && e[0] && await W.push(n(e[0].slug));
		}
		return (te, r) => {
			let i = k, a = N, o = re, s = f, c = F, l = M, u = j, p = P, m = I, h = ie, x = ae, C = d, w = A;
			return S(), g(w, null, {
				header: D(() => [b(i, { title: E(U)("guardian.access.roles.title") }, null, 8, ["title"])]),
				body: D(() => [b(C, { "screen-key": "guardian.roles" }, {
					default: D(() => [b(ne, {
						ready: !E(oe) && E(Z) !== null,
						failed: E(se),
						status: E(ce)
					}, {
						default: D(() => [v("div", z, [
							b(c, {
								data: E(ue),
								columns: E(de),
								"data-testid": "guardian-roles-table"
							}, {
								"name-cell": D(({ row: e }) => [v("div", {
									class: "flex flex-wrap items-center gap-2",
									"data-testid": `guardian-role-row-${e.original.slug}`
								}, [
									b(a, {
										to: E(n)(e.original.slug),
										class: "font-medium hover:underline"
									}, {
										default: D(() => [y(T(e.original.name), 1)]),
										_: 2
									}, 1032, ["to"]),
									e.original.super ? (S(), g(o, {
										key: 0,
										color: "error",
										variant: "subtle"
									}, {
										default: D(() => [y(T(E(U)("guardian.access.roles.super")), 1)]),
										_: 1
									})) : _("", !0),
									e.original.system ? (S(), g(o, {
										key: 1,
										color: "neutral",
										variant: "subtle"
									}, {
										default: D(() => [y(T(E(U)("guardian.access.roles.built_in")), 1)]),
										_: 1
									})) : _("", !0)
								], 8, B)]),
								"scope-cell": D(({ row: e }) => [y(T(pe(e.original.scope)), 1)]),
								"permissions-cell": D(({ row: e }) => [y(T(e.original.super ? E(U)("guardian.access.roles.everything") : e.original.permissions?.length ?? 0), 1)]),
								empty: D(() => [b(s, {
									compact: "",
									title: E(U)("guardian.access.roles.empty"),
									"data-testid": "guardian-roles-empty"
								}, null, 8, ["title"])]),
								_: 1
							}, 8, ["data", "columns"]),
							b(t, {
								current: E(Q).current,
								last: E(Q).last,
								testid: "guardian-roles",
								onGo: me
							}, null, 8, ["current", "last"]),
							b(x, null, {
								header: D(() => [v("h2", V, T(E(U)("guardian.access.roles.new")), 1)]),
								default: D(() => [v("form", {
									class: "flex max-w-3xl flex-col gap-4",
									"data-testid": "guardian-role-new-form",
									onSubmit: O(he, ["prevent"])
								}, [
									b(e, {
										action: E(G),
										testid: "guardian-role-new"
									}, null, 8, ["action"]),
									v("div", H, [
										b(u, {
											label: E(U)("guardian.access.roles.fields.name"),
											required: ""
										}, {
											default: D(() => [b(l, {
												modelValue: E($).name,
												"onUpdate:modelValue": r[0] ||= (e) => E($).name = e,
												class: "w-56",
												"data-testid": "guardian-role-new-name"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, ["label"]),
										b(u, {
											label: E(U)("guardian.access.roles.fields.slug"),
											hint: E(U)("guardian.access.roles.slug_hint")
										}, {
											default: D(() => [b(l, {
												modelValue: E($).slug,
												"onUpdate:modelValue": r[1] ||= (e) => E($).slug = e,
												class: "w-48",
												"data-testid": "guardian-role-new-slug"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, ["label", "hint"]),
										b(u, { label: E(U)("guardian.access.roles.fields.scope") }, {
											default: D(() => [b(p, {
												modelValue: E($).scope,
												"onUpdate:modelValue": r[2] ||= (e) => E($).scope = e,
												items: E(fe),
												class: "w-48",
												"data-testid": "guardian-role-new-scope"
											}, null, 8, ["modelValue", "items"])]),
											_: 1
										}, 8, ["label"])
									]),
									b(u, { label: E(U)("guardian.access.roles.fields.description") }, {
										default: D(() => [b(m, {
											modelValue: E($).description,
											"onUpdate:modelValue": r[3] ||= (e) => E($).description = e,
											rows: 2,
											class: "w-full",
											"data-testid": "guardian-role-new-description"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label"]),
									b(ee, {
										modelValue: E($).permissions,
										"onUpdate:modelValue": r[4] ||= (e) => E($).permissions = e,
										permissions: E(le),
										"id-prefix": "guardian-role-new"
									}, null, 8, ["modelValue", "permissions"]),
									v("div", null, [b(h, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: E(G).busy.value,
										"data-testid": "guardian-role-new-submit"
									}, {
										default: D(() => [y(T(E(U)("guardian.access.roles.create")), 1)]),
										_: 1
									}, 8, ["loading"])])
								], 32)]),
								_: 1
							})
						])]),
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
export { U as default };
