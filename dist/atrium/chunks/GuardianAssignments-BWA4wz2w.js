import { t as e } from "./GuardianState-DytBr9ih.js";
import { i as t, n, t as r } from "./ui-BHDIeg3q.js";
import { d as i, i as a, o, u as s } from "./guardian-D9MeowUD.js";
import { t as c } from "./GuardianErrors-DtyToZz8.js";
import { t as l } from "./GuardianPager-DnfHQ8Kk.js";
import { a as ee, i as u, l as d, n as f, o as p, u as m } from "./access-CMUXpA7X.js";
import { AtriumState as h } from "@refactor-circus/atrium";
import { Fragment as g, computed as _, createBlock as v, createCommentVNode as y, createElementBlock as b, createElementVNode as x, createTextVNode as S, createVNode as C, defineComponent as w, openBlock as T, reactive as E, ref as D, toDisplayString as O, unref as k, withCtx as A, withModifiers as j } from "vue";
import { UButton as M, UFormField as N, UInput as P, ULink as F, USelect as I, UTable as te } from "@refactor-circus/atrium/ui";
import { useI18n as ne } from "vue-i18n";
// src/components/access/GuardianAssignments.vue?vue&type=script&setup=true&lang.ts
var re = {
	class: "space-y-4",
	"data-testid": "guardian-assignments"
}, ie = { class: "space-y-4" }, ae = ["data-testid"], oe = {
	key: 1,
	class: "text-muted"
}, L = /* @__PURE__ */ w({
	__name: "GuardianAssignments",
	props: {
		scopeTypes: {},
		user: { default: null },
		scopeType: { default: null },
		scopeId: { default: null },
		filters: {
			type: Boolean,
			default: !1
		},
		client: { default: void 0 }
	},
	setup(w) {
		let L = w, { t: R } = ne(), z = s(), B = s(), V = _(() => L.scopeType !== null && L.scopeId !== null), H = D(1), U = E({
			user: "",
			role: "",
			scope_type: "",
			scope_id: ""
		}), W = D({ ...U }), G = E({
			user: "",
			role: "",
			scope_type: "",
			scope_id: ""
		}), K = _(() => {
			let e = {
				user: L.user ?? f(W.value.user),
				role: f(W.value.role),
				scope_type: V.value ? L.scopeType : f(W.value.scope_type),
				scope_id: V.value ? L.scopeId : f(W.value.scope_id)
			};
			return {
				...Object.fromEntries(Object.entries(e).filter(([, e]) => e !== null)),
				per_page: 25,
				...H.value > 1 ? { page: H.value } : {}
			};
		}), { client: q, data: J, pending: se, failed: ce, status: le, reload: Y } = i(async (e) => o(await e.GET("/role-assignments", { params: { query: K.value } })), K, L.client === void 0 ? void 0 : L.client), ue = d(q), de = _(() => J.value?.data ?? []), X = _(() => u(J.value)), fe = _(() => [
			...L.user === null ? [{
				id: "user",
				header: R("guardian.access.assignments.fields.user")
			}] : [],
			{
				id: "role",
				header: R("guardian.access.assignments.fields.role")
			},
			{
				id: "scope",
				header: R("guardian.access.assignments.fields.scope")
			},
			{
				id: "assigned_at",
				header: R("guardian.access.assignments.fields.assigned_at"),
				meta: { class: { td: "text-muted" } }
			},
			{
				id: "actions",
				meta: { class: { td: "text-right" } }
			}
		]), Z = _(() => L.scopeTypes.map((e) => ({
			label: e.key,
			value: e.type
		}))), pe = _(() => [{
			label: R("guardian.access.assignments.any_scope"),
			value: r
		}, ...Z.value]), me = _(() => [{
			label: R("guardian.access.scopes.global"),
			value: r
		}, ...Z.value]), Q = _(() => ue.value.map((e) => ({
			label: e.name,
			value: e.slug
		})));
		function $() {
			H.value = 1, W.value = { ...U };
		}
		function he() {
			Object.assign(U, {
				user: "",
				role: "",
				scope_type: "",
				scope_id: ""
			}), $();
		}
		function ge(e) {
			return e.scope_type ? `${p(e.scope_type, L.scopeTypes)} ${e.scope_id ?? ""}`.trim() : R("guardian.access.scopes.global");
		}
		async function _e() {
			if (!q) return;
			let e = V.value ? L.scopeType : f(G.scope_type);
			await z.run(async () => {
				o(await q.POST("/role-assignments", { body: {
					user: L.user ?? G.user.trim(),
					role: G.role,
					scope_type: e,
					scope_id: V.value ? L.scopeId : e === null ? null : f(G.scope_id)
				} }));
			}) && (Object.assign(G, {
				user: "",
				role: "",
				scope_type: "",
				scope_id: ""
			}), await Y());
		}
		async function ve(e) {
			let t = e.role?.name ?? e.id;
			q && window.confirm(R("guardian.access.assignments.confirm_revoke", {
				role: t,
				user: e.user_id
			})) && await B.run(async () => o(await q.DELETE("/role-assignments/{assignment}", { params: { path: { assignment: e.id } } }))) && await Y();
		}
		function ye(e) {
			H.value = e;
		}
		return (r, i) => {
			let o = P, s = N, u = I, d = M, f = F, p = h, _ = te;
			return T(), b("div", re, [w.filters ? (T(), b("form", {
				key: 0,
				class: "flex flex-wrap items-end gap-3",
				"data-testid": "guardian-assignments-filters",
				onSubmit: j($, ["prevent"])
			}, [
				w.user === null ? (T(), v(s, {
					key: 0,
					label: k(R)("guardian.access.assignments.fields.user")
				}, {
					default: A(() => [C(o, {
						modelValue: k(U).user,
						"onUpdate:modelValue": i[0] ||= (e) => k(U).user = e,
						class: "w-40",
						"data-testid": "guardian-assignments-filter-user"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"])) : y("", !0),
				C(s, { label: k(R)("guardian.access.assignments.fields.role") }, {
					default: A(() => [C(o, {
						modelValue: k(U).role,
						"onUpdate:modelValue": i[1] ||= (e) => k(U).role = e,
						class: "w-40",
						"data-testid": "guardian-assignments-filter-role"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"]),
				k(V) ? y("", !0) : (T(), b(g, { key: 1 }, [C(s, { label: k(R)("guardian.access.assignments.fields.scope_type") }, {
					default: A(() => [C(u, {
						"model-value": k(n)(k(U).scope_type),
						items: k(pe),
						class: "w-44",
						"data-testid": "guardian-assignments-filter-scope-type",
						"onUpdate:modelValue": i[2] ||= (e) => k(U).scope_type = k(t)(e)
					}, null, 8, ["model-value", "items"])]),
					_: 1
				}, 8, ["label"]), C(s, { label: k(R)("guardian.access.assignments.fields.scope_id") }, {
					default: A(() => [C(o, {
						modelValue: k(U).scope_id,
						"onUpdate:modelValue": i[3] ||= (e) => k(U).scope_id = e,
						class: "w-32",
						"data-testid": "guardian-assignments-filter-scope-id"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"])], 64)),
				C(d, {
					type: "submit",
					icon: "i-lucide-filter",
					"data-testid": "guardian-assignments-filter"
				}, {
					default: A(() => [S(O(k(R)("guardian.access.filter")), 1)]),
					_: 1
				}),
				C(d, {
					variant: "ghost",
					icon: "i-lucide-x",
					"data-testid": "guardian-assignments-clear",
					onClick: he
				}, {
					default: A(() => [S(O(k(R)("guardian.access.clear")), 1)]),
					_: 1
				})
			], 32)) : y("", !0), C(e, {
				ready: !k(se) && k(J) !== null,
				failed: k(ce),
				status: k(le),
				compact: !w.filters
			}, {
				default: A(() => [x("div", ie, [
					C(c, {
						action: k(B),
						testid: "guardian-revoke"
					}, null, 8, ["action"]),
					C(_, {
						data: k(de),
						columns: k(fe),
						"data-testid": "guardian-assignments-table"
					}, {
						"user-cell": A(({ row: e }) => [C(f, {
							to: k(m)(e.original.user_id),
							class: "hover:underline",
							"data-testid": "guardian-assignment-user"
						}, {
							default: A(() => [S(O(e.original.user_id), 1)]),
							_: 2
						}, 1032, ["to"])]),
						"role-cell": A(({ row: e }) => [x("div", { "data-testid": `guardian-assignment-${e.original.id}` }, [e.original.role ? (T(), v(f, {
							key: 0,
							to: k(ee)(e.original.role.slug),
							class: "font-medium hover:underline",
							"data-testid": "guardian-assignment-role"
						}, {
							default: A(() => [S(O(e.original.role.name), 1)]),
							_: 2
						}, 1032, ["to"])) : (T(), b("span", oe, "—"))], 8, ae)]),
						"scope-cell": A(({ row: e }) => [S(O(ge(e.original)), 1)]),
						"assigned_at-cell": A(({ row: e }) => [S(O(k(a)(e.original.assigned_at) ?? "—"), 1)]),
						"actions-cell": A(({ row: e }) => [C(d, {
							size: "xs",
							color: "error",
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": `guardian-assignment-revoke-${e.original.id}`,
							onClick: (t) => ve(e.original)
						}, {
							default: A(() => [S(O(k(R)("guardian.access.assignments.revoke")), 1)]),
							_: 1
						}, 8, ["data-testid", "onClick"])]),
						empty: A(() => [C(p, {
							compact: "",
							title: k(R)("guardian.access.assignments.empty"),
							"data-testid": "guardian-assignments-empty"
						}, null, 8, ["title"])]),
						_: 1
					}, 8, ["data", "columns"]),
					C(l, {
						current: k(X).current,
						last: k(X).last,
						testid: "guardian-assignments",
						onGo: ye
					}, null, 8, ["current", "last"]),
					x("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "guardian-assign-form",
						onSubmit: j(_e, ["prevent"])
					}, [
						w.user === null ? (T(), v(s, {
							key: 0,
							label: k(R)("guardian.access.assignments.fields.user"),
							error: k(z).error("user"),
							required: ""
						}, {
							default: A(() => [C(o, {
								modelValue: k(G).user,
								"onUpdate:modelValue": i[4] ||= (e) => k(G).user = e,
								class: "w-40",
								"data-testid": "guardian-assign-user"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])) : y("", !0),
						C(s, {
							label: k(R)("guardian.access.assignments.fields.role"),
							required: ""
						}, {
							default: A(() => [C(u, {
								modelValue: k(G).role,
								"onUpdate:modelValue": i[5] ||= (e) => k(G).role = e,
								items: k(Q),
								placeholder: k(R)("guardian.access.assignments.pick_role"),
								class: "w-48",
								"data-testid": "guardian-assign-role"
							}, null, 8, [
								"modelValue",
								"items",
								"placeholder"
							])]),
							_: 1
						}, 8, ["label"]),
						k(V) ? y("", !0) : (T(), b(g, { key: 1 }, [C(s, { label: k(R)("guardian.access.assignments.fields.scope_type") }, {
							default: A(() => [C(u, {
								"model-value": k(n)(k(G).scope_type),
								items: k(me),
								class: "w-44",
								"data-testid": "guardian-assign-scope-type",
								"onUpdate:modelValue": i[6] ||= (e) => k(G).scope_type = k(t)(e)
							}, null, 8, ["model-value", "items"])]),
							_: 1
						}, 8, ["label"]), k(G).scope_type ? (T(), v(s, {
							key: 0,
							label: k(R)("guardian.access.assignments.fields.scope_id"),
							required: ""
						}, {
							default: A(() => [C(o, {
								modelValue: k(G).scope_id,
								"onUpdate:modelValue": i[7] ||= (e) => k(G).scope_id = e,
								class: "w-32",
								"data-testid": "guardian-assign-scope-id"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"])) : y("", !0)], 64)),
						C(d, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: k(z).busy.value,
							"data-testid": "guardian-assign-submit"
						}, {
							default: A(() => [S(O(k(R)("guardian.access.assignments.assign")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32),
					C(c, {
						action: k(z),
						testid: "guardian-assign"
					}, null, 8, ["action"])
				])]),
				_: 1
			}, 8, [
				"ready",
				"failed",
				"status",
				"compact"
			])]);
		};
	}
});
export { L as t };
