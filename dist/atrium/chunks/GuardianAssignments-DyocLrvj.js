import { t as e } from "./GuardianState-DytBr9ih.js";
import { d as t, f as n, i as r, o as i } from "./guardian-BXKFb2X7.js";
import { t as a } from "./GuardianErrors-mhgGBfyk.js";
import { t as o } from "./GuardianPager-DnfHQ8Kk.js";
import { a as s, i as c, l, n as u, o as d, u as f } from "./access-Ea_ryMId.js";
import { AtriumSelect as p, AtriumState as m } from "@refactor-circus/atrium";
import { Fragment as h, computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, openBlock as w, reactive as T, ref as E, toDisplayString as D, unref as O, withCtx as k, withModifiers as A } from "vue";
import { UButton as j, UFormField as M, UInput as N, ULink as P, USelect as F, UTable as I } from "@refactor-circus/atrium/ui";
import { useI18n as ee } from "vue-i18n";
// src/components/access/GuardianAssignments.vue?vue&type=script&setup=true&lang.ts
var te = {
	class: "space-y-4",
	"data-testid": "guardian-assignments"
}, ne = { class: "space-y-4" }, re = ["data-testid"], ie = {
	key: 1,
	class: "text-muted"
}, L = /* @__PURE__ */ C({
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
	setup(C) {
		let L = C, { t: R } = ee(), z = t(), B = t(), V = g(() => L.scopeType !== null && L.scopeId !== null), H = E(1), U = T({
			user: "",
			role: "",
			scope_type: "",
			scope_id: ""
		}), W = E({ ...U }), G = T({
			user: "",
			role: "",
			scope_type: "",
			scope_id: ""
		}), K = g(() => {
			let e = {
				user: L.user ?? u(W.value.user),
				role: u(W.value.role),
				scope_type: V.value ? L.scopeType : u(W.value.scope_type),
				scope_id: V.value ? L.scopeId : u(W.value.scope_id)
			};
			return {
				...Object.fromEntries(Object.entries(e).filter(([, e]) => e !== null)),
				per_page: 25,
				...H.value > 1 ? { page: H.value } : {}
			};
		}), { client: q, data: J, pending: ae, failed: oe, status: se, reload: Y } = n(async (e) => i(await e.GET("/role-assignments", { params: { query: K.value } })), K, L.client === void 0 ? void 0 : L.client), ce = l(q), le = g(() => J.value?.data ?? []), X = g(() => c(J.value)), ue = g(() => [
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
		]), Z = g(() => L.scopeTypes.map((e) => ({
			label: e.key,
			value: e.type
		}))), Q = g(() => ce.value.map((e) => ({
			label: e.name,
			value: e.slug
		})));
		function $() {
			H.value = 1, W.value = { ...U };
		}
		function de() {
			Object.assign(U, {
				user: "",
				role: "",
				scope_type: "",
				scope_id: ""
			}), $();
		}
		function fe(e) {
			return e.scope_type ? `${d(e.scope_type, L.scopeTypes)} ${e.scope_id ?? ""}`.trim() : R("guardian.access.scopes.global");
		}
		async function pe() {
			if (!q) return;
			let e = V.value ? L.scopeType : u(G.scope_type);
			await z.run(async () => {
				i(await q.POST("/role-assignments", { body: {
					user: L.user ?? G.user.trim(),
					role: G.role,
					scope_type: e,
					scope_id: V.value ? L.scopeId : e === null ? null : u(G.scope_id)
				} }));
			}) && (Object.assign(G, {
				user: "",
				role: "",
				scope_type: "",
				scope_id: ""
			}), await Y());
		}
		async function me(e) {
			let t = e.role?.name ?? e.id;
			q && window.confirm(R("guardian.access.assignments.confirm_revoke", {
				role: t,
				user: e.user_id
			})) && await B.run(async () => i(await q.DELETE("/role-assignments/{assignment}", { params: { path: { assignment: e.id } } }))) && await Y();
		}
		function he(e) {
			H.value = e;
		}
		return (t, n) => {
			let i = N, c = M, l = p, u = j, d = P, g = m, T = I, E = F;
			return w(), y("div", te, [C.filters ? (w(), y("form", {
				key: 0,
				class: "flex flex-wrap items-end gap-3",
				"data-testid": "guardian-assignments-filters",
				onSubmit: A($, ["prevent"])
			}, [
				C.user === null ? (w(), _(c, {
					key: 0,
					label: O(R)("guardian.access.assignments.fields.user")
				}, {
					default: k(() => [S(i, {
						modelValue: O(U).user,
						"onUpdate:modelValue": n[0] ||= (e) => O(U).user = e,
						class: "w-40",
						"data-testid": "guardian-assignments-filter-user"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"])) : v("", !0),
				S(c, { label: O(R)("guardian.access.assignments.fields.role") }, {
					default: k(() => [S(i, {
						modelValue: O(U).role,
						"onUpdate:modelValue": n[1] ||= (e) => O(U).role = e,
						class: "w-40",
						"data-testid": "guardian-assignments-filter-role"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"]),
				O(V) ? v("", !0) : (w(), y(h, { key: 1 }, [S(c, { label: O(R)("guardian.access.assignments.fields.scope_type") }, {
					default: k(() => [S(l, {
						modelValue: O(U).scope_type,
						"onUpdate:modelValue": n[2] ||= (e) => O(U).scope_type = e,
						items: O(Z),
						blank: O(R)("guardian.access.assignments.any_scope"),
						class: "w-44",
						"data-testid": "guardian-assignments-filter-scope-type"
					}, null, 8, [
						"modelValue",
						"items",
						"blank"
					])]),
					_: 1
				}, 8, ["label"]), S(c, { label: O(R)("guardian.access.assignments.fields.scope_id") }, {
					default: k(() => [S(i, {
						modelValue: O(U).scope_id,
						"onUpdate:modelValue": n[3] ||= (e) => O(U).scope_id = e,
						class: "w-32",
						"data-testid": "guardian-assignments-filter-scope-id"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"])], 64)),
				S(u, {
					type: "submit",
					icon: "i-lucide-filter",
					"data-testid": "guardian-assignments-filter"
				}, {
					default: k(() => [x(D(O(R)("guardian.access.filter")), 1)]),
					_: 1
				}),
				S(u, {
					variant: "ghost",
					icon: "i-lucide-x",
					"data-testid": "guardian-assignments-clear",
					onClick: de
				}, {
					default: k(() => [x(D(O(R)("guardian.access.clear")), 1)]),
					_: 1
				})
			], 32)) : v("", !0), S(e, {
				ready: !O(ae) && O(J) !== null,
				failed: O(oe),
				status: O(se),
				compact: !C.filters
			}, {
				default: k(() => [b("div", ne, [
					S(a, {
						action: O(B),
						testid: "guardian-revoke"
					}, null, 8, ["action"]),
					S(T, {
						data: O(le),
						columns: O(ue),
						"data-testid": "guardian-assignments-table"
					}, {
						"user-cell": k(({ row: e }) => [S(d, {
							to: O(f)(e.original.user_id),
							class: "hover:underline",
							"data-testid": "guardian-assignment-user"
						}, {
							default: k(() => [x(D(e.original.user_id), 1)]),
							_: 2
						}, 1032, ["to"])]),
						"role-cell": k(({ row: e }) => [b("div", { "data-testid": `guardian-assignment-${e.original.id}` }, [e.original.role ? (w(), _(d, {
							key: 0,
							to: O(s)(e.original.role.slug),
							class: "font-medium hover:underline",
							"data-testid": "guardian-assignment-role"
						}, {
							default: k(() => [x(D(e.original.role.name), 1)]),
							_: 2
						}, 1032, ["to"])) : (w(), y("span", ie, "—"))], 8, re)]),
						"scope-cell": k(({ row: e }) => [x(D(fe(e.original)), 1)]),
						"assigned_at-cell": k(({ row: e }) => [x(D(O(r)(e.original.assigned_at) ?? "—"), 1)]),
						"actions-cell": k(({ row: e }) => [S(u, {
							size: "xs",
							color: "error",
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": `guardian-assignment-revoke-${e.original.id}`,
							onClick: (t) => me(e.original)
						}, {
							default: k(() => [x(D(O(R)("guardian.access.assignments.revoke")), 1)]),
							_: 1
						}, 8, ["data-testid", "onClick"])]),
						empty: k(() => [S(g, {
							compact: "",
							title: O(R)("guardian.access.assignments.empty"),
							"data-testid": "guardian-assignments-empty"
						}, null, 8, ["title"])]),
						_: 1
					}, 8, ["data", "columns"]),
					S(o, {
						current: O(X).current,
						last: O(X).last,
						testid: "guardian-assignments",
						onGo: he
					}, null, 8, ["current", "last"]),
					b("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "guardian-assign-form",
						onSubmit: A(pe, ["prevent"])
					}, [
						C.user === null ? (w(), _(c, {
							key: 0,
							label: O(R)("guardian.access.assignments.fields.user"),
							error: O(z).error("user"),
							required: ""
						}, {
							default: k(() => [S(i, {
								modelValue: O(G).user,
								"onUpdate:modelValue": n[4] ||= (e) => O(G).user = e,
								class: "w-40",
								"data-testid": "guardian-assign-user"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])) : v("", !0),
						S(c, {
							label: O(R)("guardian.access.assignments.fields.role"),
							required: ""
						}, {
							default: k(() => [S(E, {
								modelValue: O(G).role,
								"onUpdate:modelValue": n[5] ||= (e) => O(G).role = e,
								items: O(Q),
								placeholder: O(R)("guardian.access.assignments.pick_role"),
								class: "w-48",
								"data-testid": "guardian-assign-role"
							}, null, 8, [
								"modelValue",
								"items",
								"placeholder"
							])]),
							_: 1
						}, 8, ["label"]),
						O(V) ? v("", !0) : (w(), y(h, { key: 1 }, [S(c, { label: O(R)("guardian.access.assignments.fields.scope_type") }, {
							default: k(() => [S(l, {
								modelValue: O(G).scope_type,
								"onUpdate:modelValue": n[6] ||= (e) => O(G).scope_type = e,
								items: O(Z),
								blank: O(R)("guardian.access.scopes.global"),
								class: "w-44",
								"data-testid": "guardian-assign-scope-type"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]), O(G).scope_type ? (w(), _(c, {
							key: 0,
							label: O(R)("guardian.access.assignments.fields.scope_id"),
							required: ""
						}, {
							default: k(() => [S(i, {
								modelValue: O(G).scope_id,
								"onUpdate:modelValue": n[7] ||= (e) => O(G).scope_id = e,
								class: "w-32",
								"data-testid": "guardian-assign-scope-id"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"])) : v("", !0)], 64)),
						S(u, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: O(z).busy.value,
							"data-testid": "guardian-assign-submit"
						}, {
							default: k(() => [x(D(O(R)("guardian.access.assignments.assign")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32),
					S(a, {
						action: O(z),
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
