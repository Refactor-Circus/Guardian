import { t as e } from "./GuardianErrors-mhgGBfyk.js";
import { t } from "./GuardianPager-DnfHQ8Kk.js";
import { i as n, l as r, n as i, o as a, s as o } from "./access-Ea_ryMId.js";
import { n as s } from "./admin-_cLX5Zre.js";
import { t as ee } from "./GuardianState-DytBr9ih.js";
import { c, d as l, f as u, o as d } from "./guardian-BXKFb2X7.js";
import { AtriumIntegrationScreen as te, AtriumSelect as f, AtriumState as ne, useAtriumScreen as p, useHead as m } from "@refactor-circus/atrium";
import { Fragment as h, computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, openBlock as w, reactive as T, ref as re, renderList as E, toDisplayString as D, unref as O, watch as ie, withCtx as k, withModifiers as A } from "vue";
import { UButton as ae, UCard as oe, UCheckbox as se, UDashboardNavbar as ce, UDashboardPanel as le, UFormField as ue, UInput as de, UTable as fe } from "@refactor-circus/atrium/ui";
import { useI18n as pe } from "vue-i18n";
// src/pages/two-factor-policies/index.vue?vue&type=script&setup=true&lang.ts
var me = {
	class: "space-y-4",
	"data-testid": "guardian-two-factor-policies"
}, he = { class: "space-y-4" }, ge = ["data-testid"], j = {
	key: 0,
	class: "flex flex-wrap gap-2"
}, M = { class: "font-medium" }, N = { class: "flex flex-wrap items-start gap-3" }, P = { class: "flex flex-wrap items-center gap-4 text-sm" }, F = { class: "text-muted" }, I = { class: "text-xs text-muted" }, L = /* @__PURE__ */ C({
	__name: "index",
	setup(C) {
		let { t: L } = pe(), R = l(), z = l(), B = l();
		m({ title: () => L("guardian.admin.policies.title") });
		let { data: _e } = p("guardian.two-factor-policies"), V = g(() => o(_e.value?.data.options)), H = re(1), U = g(() => ({
			per_page: 25,
			...H.value > 1 ? { page: H.value } : {}
		})), { client: W, data: G, pending: ve, failed: ye, status: be, reload: K } = u(async (e) => d(await e.GET("/two-factor-policies", { params: { query: U.value } })), U), xe = r(W), q = g(() => G.value?.data ?? []), J = g(() => n(G.value)), Se = g(() => [
			{
				id: "scope",
				header: L("guardian.admin.policies.fields.scope")
			},
			{
				id: "role",
				header: L("guardian.admin.policies.fields.role")
			},
			{
				id: "required",
				header: L("guardian.admin.policies.fields.required")
			},
			{
				id: "methods",
				header: L("guardian.admin.policies.fields.methods")
			},
			{
				id: "grace_days",
				header: L("guardian.admin.policies.fields.grace_days"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				id: "actions",
				meta: { class: { td: "text-right whitespace-nowrap" } }
			}
		]), Ce = g(() => V.value.map((e) => ({
			label: e.key,
			value: e.type
		}))), we = g(() => xe.value.map((e) => ({
			label: e.name,
			value: e.slug
		}))), Y = T({}), X = T({
			scope_type: "",
			scope_id: "",
			role: "",
			required: !0,
			methods: [],
			grace_days: 0
		});
		ie(q, (e) => {
			for (let t of e) Y[t.id] = {
				required: t.required,
				methods: s.filter((e) => t.methods?.includes(e)),
				grace_days: t.grace_days
			};
		}, { immediate: !0 });
		function Z(e) {
			return e.scope_type ? `${a(e.scope_type, V.value)} ${e.scope_id ?? ""}`.trim() : L("guardian.admin.policies.everyone");
		}
		function Q(e) {
			return e.length === 0 ? null : s.filter((t) => e.includes(t));
		}
		function $(e) {
			let t = Number(e);
			return Number.isFinite(t) ? Math.trunc(t) : 0;
		}
		async function Te() {
			if (!W) return;
			let e = i(X.scope_type), t = {
				scope_type: e,
				scope_id: e === null ? null : i(X.scope_id),
				role: i(X.role),
				required: X.required,
				methods: Q(X.methods),
				grace_days: $(X.grace_days)
			};
			await R.run(async () => d(await W.POST("/two-factor-policies", { body: t }))) && (Object.assign(X, {
				scope_type: "",
				scope_id: "",
				role: "",
				required: !0,
				methods: [],
				grace_days: 0
			}), await K());
		}
		async function Ee(e) {
			let t = Y[e.id];
			if (!W || !t) return;
			let n = {
				required: t.required,
				methods: Q(t.methods),
				grace_days: $(t.grace_days)
			};
			await z.run(async () => d(await W.PATCH("/two-factor-policies/{policy}", {
				params: { path: { policy: e.id } },
				body: n
			}))) && await K();
		}
		async function De(e) {
			W && window.confirm(L("guardian.admin.policies.confirm_delete", { scope: Z(e) })) && await B.run(async () => d(await W.DELETE("/two-factor-policies/{policy}", { params: { path: { policy: e.id } } }))) && await K();
		}
		function Oe(e) {
			H.value = e;
		}
		return (n, r) => {
			let i = ce, a = se, o = de, l = ae, u = ne, d = fe, p = f, m = ue, g = oe, C = te, T = le;
			return w(), _(T, null, {
				header: k(() => [S(i, { title: O(L)("guardian.admin.policies.title") }, null, 8, ["title"])]),
				body: k(() => [S(C, { "screen-key": "guardian.two-factor-policies" }, {
					default: k(() => [b("div", me, [S(ee, {
						ready: !O(ve) && O(G) !== null,
						failed: O(ye),
						status: O(be)
					}, {
						default: k(() => [b("div", he, [
							S(e, {
								action: O(z),
								testid: "guardian-policy-update"
							}, null, 8, ["action"]),
							S(e, {
								action: O(B),
								testid: "guardian-policy-delete"
							}, null, 8, ["action"]),
							S(d, {
								data: O(q),
								columns: O(Se),
								"data-testid": "guardian-policies-table"
							}, {
								"scope-cell": k(({ row: e }) => [b("span", { "data-testid": `guardian-policy-${e.original.id}` }, D(Z(e.original)), 9, ge)]),
								"role-cell": k(({ row: e }) => [x(D(e.original.role ?? O(L)("guardian.admin.policies.any_role")), 1)]),
								"required-cell": k(({ row: e }) => [O(Y)[e.original.id] ? (w(), _(a, {
									key: 0,
									modelValue: O(Y)[e.original.id].required,
									"onUpdate:modelValue": (t) => O(Y)[e.original.id].required = t,
									"aria-label": O(L)("guardian.admin.policies.fields.required"),
									"data-testid": "guardian-policy-required"
								}, null, 8, [
									"modelValue",
									"onUpdate:modelValue",
									"aria-label"
								])) : v("", !0)]),
								"methods-cell": k(({ row: e }) => [O(Y)[e.original.id] ? (w(), y("div", j, [(w(!0), y(h, null, E(O(s), (t) => (w(), _(a, {
									key: t,
									"model-value": O(Y)[e.original.id].methods.includes(t),
									label: O(L)(`guardian.admin.policies.methods.${t}`),
									"data-testid": `guardian-policy-method-${t}`,
									"onUpdate:modelValue": (n) => O(Y)[e.original.id].methods = O(c)(O(Y)[e.original.id].methods, t, n)
								}, null, 8, [
									"model-value",
									"label",
									"data-testid",
									"onUpdate:modelValue"
								]))), 128))])) : v("", !0)]),
								"grace_days-cell": k(({ row: e }) => [O(Y)[e.original.id] ? (w(), _(o, {
									key: 0,
									modelValue: O(Y)[e.original.id].grace_days,
									"onUpdate:modelValue": (t) => O(Y)[e.original.id].grace_days = t,
									modelModifiers: { number: !0 },
									type: "number",
									min: "0",
									max: "3650",
									class: "w-20",
									"data-testid": "guardian-policy-grace"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])) : v("", !0)]),
								"actions-cell": k(({ row: e }) => [S(l, {
									size: "xs",
									variant: "soft",
									"data-testid": "guardian-policy-save",
									onClick: (t) => Ee(e.original)
								}, {
									default: k(() => [x(D(O(L)("guardian.admin.save")), 1)]),
									_: 1
								}, 8, ["onClick"]), S(l, {
									size: "xs",
									color: "error",
									variant: "ghost",
									"data-testid": "guardian-policy-delete",
									onClick: (t) => De(e.original)
								}, {
									default: k(() => [x(D(O(L)("guardian.admin.delete")), 1)]),
									_: 1
								}, 8, ["onClick"])]),
								empty: k(() => [S(u, {
									compact: "",
									title: O(L)("guardian.admin.policies.empty"),
									"data-testid": "guardian-policies-empty"
								}, null, 8, ["title"])]),
								_: 1
							}, 8, ["data", "columns"]),
							S(t, {
								current: O(J).current,
								last: O(J).last,
								testid: "guardian-policies",
								onGo: Oe
							}, null, 8, ["current", "last"]),
							S(g, null, {
								header: k(() => [b("h2", M, D(O(L)("guardian.admin.policies.new")), 1)]),
								default: k(() => [b("form", {
									class: "space-y-3",
									"data-testid": "guardian-policy-form",
									onSubmit: A(Te, ["prevent"])
								}, [
									b("div", N, [
										S(m, { label: O(L)("guardian.admin.policies.fields.scope_type") }, {
											default: k(() => [S(p, {
												modelValue: O(X).scope_type,
												"onUpdate:modelValue": r[0] ||= (e) => O(X).scope_type = e,
												items: O(Ce),
												blank: O(L)("guardian.admin.policies.everyone"),
												class: "w-44",
												"data-testid": "guardian-policy-new-scope-type"
											}, null, 8, [
												"modelValue",
												"items",
												"blank"
											])]),
											_: 1
										}, 8, ["label"]),
										O(X).scope_type ? (w(), _(m, {
											key: 0,
											label: O(L)("guardian.admin.policies.fields.scope_id"),
											required: ""
										}, {
											default: k(() => [S(o, {
												modelValue: O(X).scope_id,
												"onUpdate:modelValue": r[1] ||= (e) => O(X).scope_id = e,
												class: "w-32",
												"data-testid": "guardian-policy-new-scope-id"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, ["label"])) : v("", !0),
										S(m, { label: O(L)("guardian.admin.policies.fields.role") }, {
											default: k(() => [S(p, {
												modelValue: O(X).role,
												"onUpdate:modelValue": r[2] ||= (e) => O(X).role = e,
												items: O(we),
												blank: O(L)("guardian.admin.policies.any_role"),
												class: "w-48",
												"data-testid": "guardian-policy-new-role"
											}, null, 8, [
												"modelValue",
												"items",
												"blank"
											])]),
											_: 1
										}, 8, ["label"]),
										S(m, { label: O(L)("guardian.admin.policies.fields.grace_days") }, {
											default: k(() => [S(o, {
												modelValue: O(X).grace_days,
												"onUpdate:modelValue": r[3] ||= (e) => O(X).grace_days = e,
												modelModifiers: { number: !0 },
												type: "number",
												min: "0",
												max: "3650",
												class: "w-24",
												"data-testid": "guardian-policy-new-grace"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, ["label"])
									]),
									b("div", P, [
										S(a, {
											modelValue: O(X).required,
											"onUpdate:modelValue": r[4] ||= (e) => O(X).required = e,
											label: O(L)("guardian.admin.policies.fields.required"),
											"data-testid": "guardian-policy-new-required"
										}, null, 8, ["modelValue", "label"]),
										b("span", F, D(O(L)("guardian.admin.policies.fields.methods")) + ":", 1),
										(w(!0), y(h, null, E(O(s), (e) => (w(), _(a, {
											key: e,
											"model-value": O(X).methods.includes(e),
											label: O(L)(`guardian.admin.policies.methods.${e}`),
											"data-testid": `guardian-policy-new-method-${e}`,
											"onUpdate:modelValue": (t) => O(X).methods = O(c)(O(X).methods, e, t)
										}, null, 8, [
											"model-value",
											"label",
											"data-testid",
											"onUpdate:modelValue"
										]))), 128))
									]),
									b("p", I, D(O(L)("guardian.admin.policies.methods_hint")), 1),
									S(e, {
										action: O(R),
										testid: "guardian-policy-create"
									}, null, 8, ["action"]),
									S(l, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: O(R).busy.value,
										"data-testid": "guardian-policy-new-submit"
									}, {
										default: k(() => [x(D(O(L)("guardian.admin.policies.create")), 1)]),
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
export { L as default };
