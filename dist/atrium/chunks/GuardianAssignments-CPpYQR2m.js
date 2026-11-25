import { t as e } from "./GuardianState-DytBr9ih.js";
import { d as t, f as n, i as r, o as i } from "./guardian-BXKFb2X7.js";
import { t as a } from "./GuardianErrors-mhgGBfyk.js";
import { t as o } from "./GuardianPager-DnfHQ8Kk.js";
import { a as s, i as c, l, n as u, o as d, u as f } from "./access-Ea_ryMId.js";
import { AtriumSelect as ee, AtriumState as p } from "@refactor-circus/atrium";
import { Fragment as m, computed as h, createBlock as g, createCommentVNode as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, openBlock as C, reactive as w, ref as T, toDisplayString as E, unref as D, withCtx as O, withModifiers as k } from "vue";
import { UButton as A, UFormField as j, UInput as M, ULink as N, USelect as P, UTable as F } from "@refactor-circus/atrium/ui";
import { useI18n as te } from "vue-i18n";
// src/components/access/GuardianAssignments.vue?vue&type=script&setup=true&lang.ts
var ne = {
	class: "space-y-4",
	"data-testid": "guardian-assignments"
}, re = { class: "space-y-4" }, ie = ["data-testid"], ae = {
	key: 1,
	class: "text-muted"
}, I = /* @__PURE__ */ S({
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
	setup(S) {
		let I = S, { t: L } = te(), R = t(), z = t(), B = h(() => I.scopeType !== null && I.scopeId !== null), V = T(1), H = w({
			user: "",
			role: "",
			scope_type: "",
			scope_id: ""
		}), U = T({ ...H }), W = w({
			user: "",
			role: "",
			scope_type: "",
			scope_id: ""
		}), G = h(() => {
			let e = {
				user: I.user ?? u(U.value.user),
				role: u(U.value.role),
				scope_type: B.value ? I.scopeType : u(U.value.scope_type),
				scope_id: B.value ? I.scopeId : u(U.value.scope_id)
			};
			return {
				...Object.fromEntries(Object.entries(e).filter(([, e]) => e !== null)),
				per_page: 25,
				...V.value > 1 ? { page: V.value } : {}
			};
		}), { client: K, data: q, pending: oe, failed: se, status: ce, reload: J } = n(async (e) => i(await e.GET("/role-assignments", { params: { query: G.value } })), G, I.client === void 0 ? void 0 : I.client), le = l(K), ue = h(() => q.value?.data ?? []), Y = h(() => c(q.value)), de = h(() => [
			...I.user === null ? [{
				id: "user",
				header: L("guardian.access.assignments.fields.user")
			}] : [],
			{
				id: "role",
				header: L("guardian.access.assignments.fields.role")
			},
			{
				id: "scope",
				header: L("guardian.access.assignments.fields.scope")
			},
			{
				id: "assigned_at",
				header: L("guardian.access.assignments.fields.assigned_at"),
				meta: { class: { td: "text-muted" } }
			},
			{
				id: "actions",
				meta: { class: { td: "text-right" } }
			}
		]), X = h(() => I.scopeTypes.map((e) => ({
			label: e.key,
			value: e.type
		}))), Z = h(() => le.value.map((e) => ({
			label: e.name,
			value: e.slug
		})));
		function Q() {
			V.value = 1, U.value = { ...H };
		}
		function fe() {
			Object.assign(H, {
				user: "",
				role: "",
				scope_type: "",
				scope_id: ""
			}), Q();
		}
		function pe(e) {
			return e.scope_type ? `${d(e.scope_type, I.scopeTypes)} ${e.scope_label ?? e.scope_id ?? ""}`.trim() : L("guardian.access.scopes.global");
		}
		function $(e) {
			return e.user_label ?? e.user_id;
		}
		async function me() {
			if (!K) return;
			let e = B.value ? I.scopeType : u(W.scope_type);
			await R.run(async () => {
				i(await K.POST("/role-assignments", { body: {
					user: I.user ?? W.user.trim(),
					role: W.role,
					scope_type: e,
					scope_id: B.value ? I.scopeId : e === null ? null : u(W.scope_id)
				} }));
			}) && (Object.assign(W, {
				user: "",
				role: "",
				scope_type: "",
				scope_id: ""
			}), await J());
		}
		async function he(e) {
			let t = e.role?.name ?? e.id;
			K && window.confirm(L("guardian.access.assignments.confirm_revoke", {
				role: t,
				user: $(e)
			})) && await z.run(async () => i(await K.DELETE("/role-assignments/{assignment}", { params: { path: { assignment: e.id } } }))) && await J();
		}
		function ge(e) {
			V.value = e;
		}
		return (t, n) => {
			let i = M, c = j, l = ee, u = A, d = N, h = p, w = F, T = P;
			return C(), v("div", ne, [S.filters ? (C(), v("form", {
				key: 0,
				class: "flex flex-wrap items-start gap-3",
				"data-testid": "guardian-assignments-filters",
				onSubmit: k(Q, ["prevent"])
			}, [
				S.user === null ? (C(), g(c, {
					key: 0,
					label: D(L)("guardian.access.assignments.fields.user")
				}, {
					default: O(() => [x(i, {
						modelValue: D(H).user,
						"onUpdate:modelValue": n[0] ||= (e) => D(H).user = e,
						class: "w-40",
						"data-testid": "guardian-assignments-filter-user"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"])) : _("", !0),
				x(c, { label: D(L)("guardian.access.assignments.fields.role") }, {
					default: O(() => [x(i, {
						modelValue: D(H).role,
						"onUpdate:modelValue": n[1] ||= (e) => D(H).role = e,
						class: "w-40",
						"data-testid": "guardian-assignments-filter-role"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"]),
				D(B) ? _("", !0) : (C(), v(m, { key: 1 }, [x(c, { label: D(L)("guardian.access.assignments.fields.scope_type") }, {
					default: O(() => [x(l, {
						modelValue: D(H).scope_type,
						"onUpdate:modelValue": n[2] ||= (e) => D(H).scope_type = e,
						items: D(X),
						blank: D(L)("guardian.access.assignments.any_scope"),
						class: "w-44",
						"data-testid": "guardian-assignments-filter-scope-type"
					}, null, 8, [
						"modelValue",
						"items",
						"blank"
					])]),
					_: 1
				}, 8, ["label"]), x(c, { label: D(L)("guardian.access.assignments.fields.scope_id") }, {
					default: O(() => [x(i, {
						modelValue: D(H).scope_id,
						"onUpdate:modelValue": n[3] ||= (e) => D(H).scope_id = e,
						class: "w-32",
						"data-testid": "guardian-assignments-filter-scope-id"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label"])], 64)),
				x(u, {
					type: "submit",
					icon: "i-lucide-filter",
					class: "mt-field-label",
					"data-testid": "guardian-assignments-filter"
				}, {
					default: O(() => [b(E(D(L)("guardian.access.filter")), 1)]),
					_: 1
				}),
				x(u, {
					variant: "ghost",
					icon: "i-lucide-x",
					class: "mt-field-label",
					"data-testid": "guardian-assignments-clear",
					onClick: fe
				}, {
					default: O(() => [b(E(D(L)("guardian.access.clear")), 1)]),
					_: 1
				})
			], 32)) : _("", !0), x(e, {
				ready: !D(oe) && D(q) !== null,
				failed: D(se),
				status: D(ce),
				compact: !S.filters
			}, {
				default: O(() => [y("div", re, [
					x(a, {
						action: D(z),
						testid: "guardian-revoke"
					}, null, 8, ["action"]),
					x(w, {
						data: D(ue),
						columns: D(de),
						"data-testid": "guardian-assignments-table"
					}, {
						"user-cell": O(({ row: e }) => [x(d, {
							to: D(f)(e.original.user_id),
							class: "hover:underline",
							"data-testid": "guardian-assignment-user"
						}, {
							default: O(() => [b(E($(e.original)), 1)]),
							_: 2
						}, 1032, ["to"])]),
						"role-cell": O(({ row: e }) => [y("div", { "data-testid": `guardian-assignment-${e.original.id}` }, [e.original.role ? (C(), g(d, {
							key: 0,
							to: D(s)(e.original.role.slug),
							class: "font-medium hover:underline",
							"data-testid": "guardian-assignment-role"
						}, {
							default: O(() => [b(E(e.original.role.name), 1)]),
							_: 2
						}, 1032, ["to"])) : (C(), v("span", ae, "—"))], 8, ie)]),
						"scope-cell": O(({ row: e }) => [b(E(pe(e.original)), 1)]),
						"assigned_at-cell": O(({ row: e }) => [b(E(D(r)(e.original.assigned_at) ?? "—"), 1)]),
						"actions-cell": O(({ row: e }) => [x(u, {
							size: "xs",
							color: "error",
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": `guardian-assignment-revoke-${e.original.id}`,
							onClick: (t) => he(e.original)
						}, {
							default: O(() => [b(E(D(L)("guardian.access.assignments.revoke")), 1)]),
							_: 1
						}, 8, ["data-testid", "onClick"])]),
						empty: O(() => [x(h, {
							compact: "",
							title: D(L)("guardian.access.assignments.empty"),
							"data-testid": "guardian-assignments-empty"
						}, null, 8, ["title"])]),
						_: 1
					}, 8, ["data", "columns"]),
					x(o, {
						current: D(Y).current,
						last: D(Y).last,
						testid: "guardian-assignments",
						onGo: ge
					}, null, 8, ["current", "last"]),
					y("form", {
						class: "flex flex-wrap items-start gap-3",
						"data-testid": "guardian-assign-form",
						onSubmit: k(me, ["prevent"])
					}, [
						S.user === null ? (C(), g(c, {
							key: 0,
							label: D(L)("guardian.access.assignments.fields.user"),
							error: D(R).error("user"),
							required: ""
						}, {
							default: O(() => [x(i, {
								modelValue: D(W).user,
								"onUpdate:modelValue": n[4] ||= (e) => D(W).user = e,
								class: "w-40",
								"data-testid": "guardian-assign-user"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])) : _("", !0),
						x(c, {
							label: D(L)("guardian.access.assignments.fields.role"),
							required: ""
						}, {
							default: O(() => [x(T, {
								modelValue: D(W).role,
								"onUpdate:modelValue": n[5] ||= (e) => D(W).role = e,
								items: D(Z),
								placeholder: D(L)("guardian.access.assignments.pick_role"),
								class: "w-48",
								"data-testid": "guardian-assign-role"
							}, null, 8, [
								"modelValue",
								"items",
								"placeholder"
							])]),
							_: 1
						}, 8, ["label"]),
						D(B) ? _("", !0) : (C(), v(m, { key: 1 }, [x(c, { label: D(L)("guardian.access.assignments.fields.scope_type") }, {
							default: O(() => [x(l, {
								modelValue: D(W).scope_type,
								"onUpdate:modelValue": n[6] ||= (e) => D(W).scope_type = e,
								items: D(X),
								blank: D(L)("guardian.access.scopes.global"),
								class: "w-44",
								"data-testid": "guardian-assign-scope-type"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]), D(W).scope_type ? (C(), g(c, {
							key: 0,
							label: D(L)("guardian.access.assignments.fields.scope_id"),
							required: ""
						}, {
							default: O(() => [x(i, {
								modelValue: D(W).scope_id,
								"onUpdate:modelValue": n[7] ||= (e) => D(W).scope_id = e,
								class: "w-32",
								"data-testid": "guardian-assign-scope-id"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"])) : _("", !0)], 64)),
						x(u, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: D(R).busy.value,
							class: "mt-field-label",
							"data-testid": "guardian-assign-submit"
						}, {
							default: O(() => [b(E(D(L)("guardian.access.assignments.assign")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32),
					x(a, {
						action: D(R),
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
export { I as t };
