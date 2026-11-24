import { t as e } from "./GuardianErrors-DtyToZz8.js";
import { t } from "./GuardianPager-DnfHQ8Kk.js";
import { i as n, l as r, n as i, o as a, s as o } from "./access-CMUXpA7X.js";
import { n as s } from "./admin-_cLX5Zre.js";
import { t as ee } from "./GuardianState-DytBr9ih.js";
import { i as c, n as l, r as u, t as d } from "./ui-BHDIeg3q.js";
import { d as f, o as p, u as m } from "./guardian-D9MeowUD.js";
import { AtriumIntegrationScreen as te, AtriumState as ne, useAtriumScreen as h, useHead as g } from "@refactor-circus/atrium";
import { Fragment as _, computed as v, createBlock as y, createCommentVNode as b, createElementBlock as x, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, openBlock as E, reactive as D, ref as re, renderList as O, toDisplayString as k, unref as A, watch as ie, withCtx as j, withModifiers as ae } from "vue";
import { UButton as oe, UCard as se, UCheckbox as ce, UDashboardNavbar as le, UDashboardPanel as ue, UFormField as de, UInput as fe, USelect as pe, UTable as me } from "@refactor-circus/atrium/ui";
import { useI18n as he } from "vue-i18n";
// src/pages/two-factor-policies/index.vue?vue&type=script&setup=true&lang.ts
var ge = {
	class: "space-y-4",
	"data-testid": "guardian-two-factor-policies"
}, _e = { class: "space-y-4" }, ve = ["data-testid"], M = {
	key: 0,
	class: "flex flex-wrap gap-2"
}, N = { class: "font-medium" }, P = { class: "flex flex-wrap items-end gap-3" }, F = { class: "flex flex-wrap items-center gap-4 text-sm" }, ye = { class: "text-muted" }, be = { class: "text-xs text-muted" }, I = /* @__PURE__ */ T({
	__name: "index",
	setup(T) {
		let { t: I } = he(), L = m(), R = m(), z = m();
		g({ title: () => I("guardian.admin.policies.title") });
		let { data: xe } = h("guardian.two-factor-policies"), B = v(() => o(xe.value?.data.options)), V = re(1), H = v(() => ({
			per_page: 25,
			...V.value > 1 ? { page: V.value } : {}
		})), { client: U, data: W, pending: Se, failed: Ce, status: we, reload: G } = f(async (e) => p(await e.GET("/two-factor-policies", { params: { query: H.value } })), H), Te = r(U), K = v(() => W.value?.data ?? []), q = v(() => n(W.value)), Ee = v(() => [
			{
				id: "scope",
				header: I("guardian.admin.policies.fields.scope")
			},
			{
				id: "role",
				header: I("guardian.admin.policies.fields.role")
			},
			{
				id: "required",
				header: I("guardian.admin.policies.fields.required")
			},
			{
				id: "methods",
				header: I("guardian.admin.policies.fields.methods")
			},
			{
				id: "grace_days",
				header: I("guardian.admin.policies.fields.grace_days"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				id: "actions",
				meta: { class: { td: "text-right whitespace-nowrap" } }
			}
		]), J = v(() => [{
			label: I("guardian.admin.policies.everyone"),
			value: d
		}, ...B.value.map((e) => ({
			label: e.key,
			value: e.type
		}))]), De = v(() => [{
			label: I("guardian.admin.policies.any_role"),
			value: d
		}, ...Te.value.map((e) => ({
			label: e.name,
			value: e.slug
		}))]), Y = D({}), X = D({
			scope_type: "",
			scope_id: "",
			role: "",
			required: !0,
			methods: [],
			grace_days: 0
		});
		ie(K, (e) => {
			for (let t of e) Y[t.id] = {
				required: t.required,
				methods: s.filter((e) => t.methods?.includes(e)),
				grace_days: t.grace_days
			};
		}, { immediate: !0 });
		function Z(e) {
			return e.scope_type ? `${a(e.scope_type, B.value)} ${e.scope_id ?? ""}`.trim() : I("guardian.admin.policies.everyone");
		}
		function Q(e) {
			return e.length === 0 ? null : s.filter((t) => e.includes(t));
		}
		function $(e) {
			let t = Number(e);
			return Number.isFinite(t) ? Math.trunc(t) : 0;
		}
		async function Oe() {
			if (!U) return;
			let e = i(X.scope_type), t = {
				scope_type: e,
				scope_id: e === null ? null : i(X.scope_id),
				role: i(X.role),
				required: X.required,
				methods: Q(X.methods),
				grace_days: $(X.grace_days)
			};
			await L.run(async () => p(await U.POST("/two-factor-policies", { body: t }))) && (Object.assign(X, {
				scope_type: "",
				scope_id: "",
				role: "",
				required: !0,
				methods: [],
				grace_days: 0
			}), await G());
		}
		async function ke(e) {
			let t = Y[e.id];
			if (!U || !t) return;
			let n = {
				required: t.required,
				methods: Q(t.methods),
				grace_days: $(t.grace_days)
			};
			await R.run(async () => p(await U.PATCH("/two-factor-policies/{policy}", {
				params: { path: { policy: e.id } },
				body: n
			}))) && await G();
		}
		async function Ae(e) {
			U && window.confirm(I("guardian.admin.policies.confirm_delete", { scope: Z(e) })) && await z.run(async () => p(await U.DELETE("/two-factor-policies/{policy}", { params: { path: { policy: e.id } } }))) && await G();
		}
		function je(e) {
			V.value = e;
		}
		return (n, r) => {
			let i = le, a = ce, o = fe, d = oe, f = ne, p = me, m = pe, h = de, g = se, v = te, T = ue;
			return E(), y(T, null, {
				header: j(() => [w(i, { title: A(I)("guardian.admin.policies.title") }, null, 8, ["title"])]),
				body: j(() => [w(v, { "screen-key": "guardian.two-factor-policies" }, {
					default: j(() => [S("div", ge, [w(ee, {
						ready: !A(Se) && A(W) !== null,
						failed: A(Ce),
						status: A(we)
					}, {
						default: j(() => [S("div", _e, [
							w(e, {
								action: A(R),
								testid: "guardian-policy-update"
							}, null, 8, ["action"]),
							w(e, {
								action: A(z),
								testid: "guardian-policy-delete"
							}, null, 8, ["action"]),
							w(p, {
								data: A(K),
								columns: A(Ee),
								"data-testid": "guardian-policies-table"
							}, {
								"scope-cell": j(({ row: e }) => [S("span", { "data-testid": `guardian-policy-${e.original.id}` }, k(Z(e.original)), 9, ve)]),
								"role-cell": j(({ row: e }) => [C(k(e.original.role ?? A(I)("guardian.admin.policies.any_role")), 1)]),
								"required-cell": j(({ row: e }) => [A(Y)[e.original.id] ? (E(), y(a, {
									key: 0,
									modelValue: A(Y)[e.original.id].required,
									"onUpdate:modelValue": (t) => A(Y)[e.original.id].required = t,
									"aria-label": A(I)("guardian.admin.policies.fields.required"),
									"data-testid": "guardian-policy-required"
								}, null, 8, [
									"modelValue",
									"onUpdate:modelValue",
									"aria-label"
								])) : b("", !0)]),
								"methods-cell": j(({ row: e }) => [A(Y)[e.original.id] ? (E(), x("div", M, [(E(!0), x(_, null, O(A(s), (t) => (E(), y(a, {
									key: t,
									"model-value": A(Y)[e.original.id].methods.includes(t),
									label: A(I)(`guardian.admin.policies.methods.${t}`),
									"data-testid": `guardian-policy-method-${t}`,
									"onUpdate:modelValue": (n) => A(Y)[e.original.id].methods = A(u)(A(Y)[e.original.id].methods, t, n)
								}, null, 8, [
									"model-value",
									"label",
									"data-testid",
									"onUpdate:modelValue"
								]))), 128))])) : b("", !0)]),
								"grace_days-cell": j(({ row: e }) => [A(Y)[e.original.id] ? (E(), y(o, {
									key: 0,
									modelValue: A(Y)[e.original.id].grace_days,
									"onUpdate:modelValue": (t) => A(Y)[e.original.id].grace_days = t,
									modelModifiers: { number: !0 },
									type: "number",
									min: "0",
									max: "3650",
									class: "w-20",
									"data-testid": "guardian-policy-grace"
								}, null, 8, ["modelValue", "onUpdate:modelValue"])) : b("", !0)]),
								"actions-cell": j(({ row: e }) => [w(d, {
									size: "xs",
									variant: "soft",
									"data-testid": "guardian-policy-save",
									onClick: (t) => ke(e.original)
								}, {
									default: j(() => [C(k(A(I)("guardian.admin.save")), 1)]),
									_: 1
								}, 8, ["onClick"]), w(d, {
									size: "xs",
									color: "error",
									variant: "ghost",
									"data-testid": "guardian-policy-delete",
									onClick: (t) => Ae(e.original)
								}, {
									default: j(() => [C(k(A(I)("guardian.admin.delete")), 1)]),
									_: 1
								}, 8, ["onClick"])]),
								empty: j(() => [w(f, {
									compact: "",
									title: A(I)("guardian.admin.policies.empty"),
									"data-testid": "guardian-policies-empty"
								}, null, 8, ["title"])]),
								_: 1
							}, 8, ["data", "columns"]),
							w(t, {
								current: A(q).current,
								last: A(q).last,
								testid: "guardian-policies",
								onGo: je
							}, null, 8, ["current", "last"]),
							w(g, null, {
								header: j(() => [S("h2", N, k(A(I)("guardian.admin.policies.new")), 1)]),
								default: j(() => [S("form", {
									class: "space-y-3",
									"data-testid": "guardian-policy-form",
									onSubmit: ae(Oe, ["prevent"])
								}, [
									S("div", P, [
										w(h, { label: A(I)("guardian.admin.policies.fields.scope_type") }, {
											default: j(() => [w(m, {
												"model-value": A(l)(A(X).scope_type),
												items: A(J),
												class: "w-44",
												"data-testid": "guardian-policy-new-scope-type",
												"onUpdate:modelValue": r[0] ||= (e) => A(X).scope_type = A(c)(e)
											}, null, 8, ["model-value", "items"])]),
											_: 1
										}, 8, ["label"]),
										A(X).scope_type ? (E(), y(h, {
											key: 0,
											label: A(I)("guardian.admin.policies.fields.scope_id"),
											required: ""
										}, {
											default: j(() => [w(o, {
												modelValue: A(X).scope_id,
												"onUpdate:modelValue": r[1] ||= (e) => A(X).scope_id = e,
												class: "w-32",
												"data-testid": "guardian-policy-new-scope-id"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, ["label"])) : b("", !0),
										w(h, { label: A(I)("guardian.admin.policies.fields.role") }, {
											default: j(() => [w(m, {
												"model-value": A(l)(A(X).role),
												items: A(De),
												class: "w-48",
												"data-testid": "guardian-policy-new-role",
												"onUpdate:modelValue": r[2] ||= (e) => A(X).role = A(c)(e)
											}, null, 8, ["model-value", "items"])]),
											_: 1
										}, 8, ["label"]),
										w(h, { label: A(I)("guardian.admin.policies.fields.grace_days") }, {
											default: j(() => [w(o, {
												modelValue: A(X).grace_days,
												"onUpdate:modelValue": r[3] ||= (e) => A(X).grace_days = e,
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
									S("div", F, [
										w(a, {
											modelValue: A(X).required,
											"onUpdate:modelValue": r[4] ||= (e) => A(X).required = e,
											label: A(I)("guardian.admin.policies.fields.required"),
											"data-testid": "guardian-policy-new-required"
										}, null, 8, ["modelValue", "label"]),
										S("span", ye, k(A(I)("guardian.admin.policies.fields.methods")) + ":", 1),
										(E(!0), x(_, null, O(A(s), (e) => (E(), y(a, {
											key: e,
											"model-value": A(X).methods.includes(e),
											label: A(I)(`guardian.admin.policies.methods.${e}`),
											"data-testid": `guardian-policy-new-method-${e}`,
											"onUpdate:modelValue": (t) => A(X).methods = A(u)(A(X).methods, e, t)
										}, null, 8, [
											"model-value",
											"label",
											"data-testid",
											"onUpdate:modelValue"
										]))), 128))
									]),
									S("p", be, k(A(I)("guardian.admin.policies.methods_hint")), 1),
									w(e, {
										action: A(L),
										testid: "guardian-policy-create"
									}, null, 8, ["action"]),
									w(d, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: A(L).busy.value,
										"data-testid": "guardian-policy-new-submit"
									}, {
										default: j(() => [C(k(A(I)("guardian.admin.policies.create")), 1)]),
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
export { I as default };
