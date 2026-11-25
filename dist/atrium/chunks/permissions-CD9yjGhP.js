import { t as e } from "./GuardianErrors-mhgGBfyk.js";
import { t } from "./GuardianPager-DnfHQ8Kk.js";
import { i as n, n as r } from "./access-Ea_ryMId.js";
import { t as i } from "./GuardianState-DytBr9ih.js";
import { d as a, f as o, o as s } from "./guardian-BXKFb2X7.js";
import { AtriumIntegrationScreen as c, AtriumState as ee, useHead as l } from "@refactor-circus/atrium";
import { computed as u, createBlock as d, createCommentVNode as f, createElementVNode as p, createTextVNode as m, createVNode as h, defineComponent as g, isRef as _, openBlock as v, reactive as y, ref as b, toDisplayString as x, unref as S, watch as C, withCtx as w, withModifiers as T } from "vue";
import { UBadge as E, UButton as D, UCard as O, UDashboardNavbar as k, UDashboardPanel as A, UFormField as j, UInput as M, UTable as N } from "@refactor-circus/atrium/ui";
import { useI18n as P } from "vue-i18n";
// src/pages/permissions/index.vue?vue&type=script&setup=true&lang.ts
var te = {
	class: "space-y-4",
	"data-testid": "guardian-permissions"
}, ne = { class: "space-y-4" }, re = ["data-testid"], ie = { class: "font-medium" }, F = /* @__PURE__ */ g({
	__name: "index",
	setup(g) {
		let { t: F } = P(), I = a(), L = a(), R = a();
		l({ title: () => F("guardian.access.permissions.title") });
		let z = b(1), B = b(""), V = b(""), H = u(() => ({
			per_page: 50,
			...V.value ? { search: V.value } : {},
			...z.value > 1 ? { page: z.value } : {}
		})), { client: U, data: W, pending: G, failed: K, status: q, reload: J } = o(async (e) => s(await e.GET("/permissions", { params: { query: H.value } })), H), Y = u(() => W.value?.data ?? []), X = u(() => n(W.value)), ae = u(() => [
			{
				id: "name",
				header: F("guardian.access.permissions.fields.name")
			},
			{
				id: "description",
				header: F("guardian.access.permissions.fields.description")
			},
			{
				id: "actions",
				meta: { class: { td: "text-right whitespace-nowrap" } }
			}
		]), Z = y({}), Q = y({
			name: "",
			description: ""
		});
		C(Y, (e) => {
			for (let t of e) Z[t.name] = {
				name: t.name,
				description: t.description ?? ""
			};
		}, { immediate: !0 });
		function oe() {
			z.value = 1, V.value = B.value.trim();
		}
		function $(e) {
			z.value = e;
		}
		async function se() {
			U && await I.run(async () => s(await U.POST("/permissions", { body: {
				name: Q.name.trim(),
				description: r(Q.description)
			} }))) && (Q.name = "", Q.description = "", await J());
		}
		async function ce(e) {
			let t = Z[e.name];
			if (!U || !t) return;
			let n = e.system ? { description: r(t.description) } : {
				name: t.name.trim(),
				description: r(t.description)
			};
			await L.run(async () => s(await U.PATCH("/permissions/{permission}", {
				params: { path: { permission: e.name } },
				body: n
			}))) && await J();
		}
		async function le(e) {
			U && !e.system && window.confirm(F("guardian.access.permissions.confirm_delete", { name: e.name })) && await R.run(async () => s(await U.DELETE("/permissions/{permission}", { params: { path: { permission: e.name } } }))) && await J();
		}
		return (n, r) => {
			let a = k, o = M, s = j, l = D, u = E, g = ee, y = N, b = O, C = c, P = A;
			return v(), d(P, null, {
				header: w(() => [h(a, { title: S(F)("guardian.access.permissions.title") }, null, 8, ["title"])]),
				body: w(() => [h(C, { "screen-key": "guardian.permissions" }, {
					default: w(() => [p("div", te, [p("form", {
						class: "flex flex-wrap items-start gap-3",
						"data-testid": "guardian-permissions-search",
						onSubmit: T(oe, ["prevent"])
					}, [h(s, { label: S(F)("guardian.access.permissions.search") }, {
						default: w(() => [h(o, {
							modelValue: S(B),
							"onUpdate:modelValue": r[0] ||= (e) => _(B) ? B.value = e : null,
							class: "w-64",
							"data-testid": "guardian-permissions-search-input"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, ["label"]), h(l, {
						type: "submit",
						icon: "i-lucide-search",
						class: "mt-field-label"
					}, {
						default: w(() => [m(x(S(F)("guardian.access.filter")), 1)]),
						_: 1
					})], 32), h(i, {
						ready: !S(G) && S(W) !== null,
						failed: S(K),
						status: S(q)
					}, {
						default: w(() => [p("div", ne, [
							h(e, {
								action: S(L),
								testid: "guardian-permission-update"
							}, null, 8, ["action"]),
							h(e, {
								action: S(R),
								testid: "guardian-permission-delete"
							}, null, 8, ["action"]),
							h(y, {
								data: S(Y),
								columns: S(ae),
								"data-testid": "guardian-permissions-table"
							}, {
								"name-cell": w(({ row: e }) => [p("div", {
									class: "flex items-center gap-2",
									"data-testid": `guardian-permission-${e.original.name}`
								}, [S(Z)[e.original.name] ? (v(), d(o, {
									key: 0,
									modelValue: S(Z)[e.original.name].name,
									"onUpdate:modelValue": (t) => S(Z)[e.original.name].name = t,
									disabled: e.original.system,
									class: "w-56",
									"data-testid": "guardian-permission-name"
								}, null, 8, [
									"modelValue",
									"onUpdate:modelValue",
									"disabled"
								])) : f("", !0), e.original.system ? (v(), d(u, {
									key: 1,
									color: "neutral",
									variant: "subtle"
								}, {
									default: w(() => [m(x(S(F)("guardian.access.permissions.built_in")), 1)]),
									_: 1
								})) : f("", !0)], 8, re)]),
								"description-cell": w(({ row: e }) => [S(Z)[e.original.name] ? (v(), d(o, {
									key: 0,
									modelValue: S(Z)[e.original.name].description,
									"onUpdate:modelValue": (t) => S(Z)[e.original.name].description = t,
									class: "w-full",
									"data-testid": "guardian-permission-description"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])) : f("", !0)]),
								"actions-cell": w(({ row: e }) => [h(l, {
									size: "xs",
									variant: "soft",
									"data-testid": "guardian-permission-save",
									onClick: (t) => ce(e.original)
								}, {
									default: w(() => [m(x(S(F)("guardian.access.save")), 1)]),
									_: 1
								}, 8, ["onClick"]), h(l, {
									size: "xs",
									color: "error",
									variant: "ghost",
									disabled: e.original.system,
									"data-testid": "guardian-permission-delete",
									onClick: (t) => le(e.original)
								}, {
									default: w(() => [m(x(S(F)("guardian.access.permissions.delete")), 1)]),
									_: 1
								}, 8, ["disabled", "onClick"])]),
								empty: w(() => [h(g, {
									compact: "",
									title: S(F)("guardian.access.permissions.empty"),
									"data-testid": "guardian-permissions-empty"
								}, null, 8, ["title"])]),
								_: 1
							}, 8, ["data", "columns"]),
							h(t, {
								current: S(X).current,
								last: S(X).last,
								testid: "guardian-permissions",
								onGo: $
							}, null, 8, ["current", "last"]),
							h(b, null, {
								header: w(() => [p("h2", ie, x(S(F)("guardian.access.permissions.new")), 1)]),
								default: w(() => [p("form", {
									class: "flex flex-wrap items-start gap-3",
									"data-testid": "guardian-permission-new-form",
									onSubmit: T(se, ["prevent"])
								}, [
									h(s, {
										label: S(F)("guardian.access.permissions.fields.name"),
										hint: S(F)("guardian.access.permissions.name_hint"),
										error: S(I).error("name"),
										required: ""
									}, {
										default: w(() => [h(o, {
											modelValue: S(Q).name,
											"onUpdate:modelValue": r[1] ||= (e) => S(Q).name = e,
											class: "w-56",
											"data-testid": "guardian-permission-new-name"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									h(s, {
										label: S(F)("guardian.access.permissions.fields.description"),
										error: S(I).error("description")
									}, {
										default: w(() => [h(o, {
											modelValue: S(Q).description,
											"onUpdate:modelValue": r[2] ||= (e) => S(Q).description = e,
											class: "w-72",
											"data-testid": "guardian-permission-new-description"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									h(l, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: S(I).busy.value,
										class: "mt-field-label",
										"data-testid": "guardian-permission-new-submit"
									}, {
										default: w(() => [m(x(S(F)("guardian.access.permissions.create")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32)]),
								_: 1
							})
						])]),
						_: 1
					}, 8, [
						"ready",
						"failed",
						"status"
					])])]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { F as default };
