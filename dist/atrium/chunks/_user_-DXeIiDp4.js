import { n as e, s as t } from "./access-Ea_ryMId.js";
import { t as n } from "./GuardianState-DytBr9ih.js";
import { f as r, o as i } from "./guardian-BXKFb2X7.js";
import { t as a } from "./GuardianAssignments-D6Vkr3K6.js";
import { t as o } from "./UserSecurity-C9bX42tG.js";
import { AtriumIntegrationScreen as s, AtriumSelect as c, useAtriumScreen as l, useHead as u } from "@refactor-circus/atrium";
import { Fragment as d, computed as f, createBlock as p, createCommentVNode as m, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as y, openBlock as b, reactive as x, ref as S, renderList as C, toDisplayString as w, unref as T, withCtx as E, withModifiers as D } from "vue";
import { UBadge as O, UButton as k, UCard as A, UDashboardNavbar as j, UDashboardPanel as M, UFormField as N, UInput as P } from "@refactor-circus/atrium/ui";
import { useI18n as F } from "vue-i18n";
import { useRoute as I } from "vue-router";
// src/components/users/UserAbilities.vue?vue&type=script&setup=true&lang.ts
var L = { class: "font-semibold" }, R = { class: "space-y-3" }, z = {
	key: 0,
	"data-testid": "guardian-abilities-super"
}, B = {
	key: 1,
	class: "text-sm text-muted"
}, V = {
	key: 2,
	class: "flex flex-wrap gap-1",
	"data-testid": "guardian-abilities-list"
}, H = /* @__PURE__ */ y({
	__name: "UserAbilities",
	props: {
		user: {},
		scopeTypes: {}
	},
	setup(t) {
		let a = t, { t: o } = F(), s = x({
			scope_type: "",
			scope_id: ""
		}), l = S({
			scope_type: null,
			scope_id: null
		}), u = f(() => ({
			user: a.user,
			...l.value
		})), { data: y, pending: j, failed: M, status: I } = r(async (e) => i(await e.GET("/users/{user}/abilities", { params: {
			path: { user: a.user },
			query: l.value.scope_type ? {
				scope_type: l.value.scope_type,
				scope_id: l.value.scope_id
			} : {}
		} })).data, u), H = f(() => a.scopeTypes.map((e) => ({
			label: e.key,
			value: e.type
		})));
		function U() {
			let t = e(s.scope_type);
			l.value = {
				scope_type: t,
				scope_id: t === null ? null : e(s.scope_id)
			};
		}
		return (e, t) => {
			let r = c, i = N, a = P, l = k, u = O, f = A;
			return b(), p(f, { "data-testid": "guardian-user-abilities" }, {
				header: E(() => [g("h2", L, w(T(o)("guardian.access.user.abilities")), 1)]),
				default: E(() => [g("div", R, [g("form", {
					class: "flex flex-wrap items-end gap-3",
					"data-testid": "guardian-abilities-scope",
					onSubmit: D(U, ["prevent"])
				}, [
					v(i, { label: T(o)("guardian.access.assignments.fields.scope_type") }, {
						default: E(() => [v(r, {
							modelValue: T(s).scope_type,
							"onUpdate:modelValue": t[0] ||= (e) => T(s).scope_type = e,
							items: T(H),
							blank: T(o)("guardian.access.scopes.global"),
							class: "w-44",
							"data-testid": "guardian-abilities-scope-type"
						}, null, 8, [
							"modelValue",
							"items",
							"blank"
						])]),
						_: 1
					}, 8, ["label"]),
					T(s).scope_type ? (b(), p(i, {
						key: 0,
						label: T(o)("guardian.access.assignments.fields.scope_id")
					}, {
						default: E(() => [v(a, {
							modelValue: T(s).scope_id,
							"onUpdate:modelValue": t[1] ||= (e) => T(s).scope_id = e,
							class: "w-32",
							"data-testid": "guardian-abilities-scope-id"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, ["label"])) : m("", !0),
					v(l, {
						type: "submit",
						size: "sm",
						variant: "soft",
						"data-testid": "guardian-abilities-apply"
					}, {
						default: E(() => [_(w(T(o)("guardian.access.user.show_abilities")), 1)]),
						_: 1
					})
				], 32), v(n, {
					ready: !T(j) && T(y) !== null,
					failed: T(M),
					status: T(I),
					compact: ""
				}, {
					default: E(() => [T(y)?.super_admin ? (b(), h("p", z, w(T(o)("guardian.access.user.super_admin")), 1)) : (T(y)?.abilities ?? []).length === 0 ? (b(), h("p", B, w(T(o)("guardian.access.user.no_abilities")), 1)) : (b(), h("ul", V, [(b(!0), h(d, null, C(T(y)?.abilities ?? [], (e) => (b(), h("li", { key: e }, [v(u, {
						color: "neutral",
						variant: "subtle"
					}, {
						default: E(() => [g("code", null, w(e), 1)]),
						_: 2
					}, 1024)]))), 128))]))]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])])]),
				_: 1
			});
		};
	}
}), U = { class: "font-semibold" }, W = /* @__PURE__ */ y({
	__name: "UserAssignments",
	props: {
		user: {},
		scopeTypes: {}
	},
	setup(e) {
		let { t } = F();
		return (n, r) => {
			let i = A;
			return b(), p(i, { "data-testid": "guardian-user-assignments" }, {
				header: E(() => [g("h2", U, w(T(t)("guardian.access.user.assignments")), 1)]),
				default: E(() => [v(a, {
					user: e.user,
					"scope-types": e.scopeTypes
				}, null, 8, ["user", "scope-types"])]),
				_: 1
			});
		};
	}
}), G = { class: "grid gap-4 lg:grid-cols-2" }, K = { class: "lg:col-span-2" }, q = { class: "lg:col-span-2" }, J = /* @__PURE__ */ y({
	__name: "[user]",
	setup(e) {
		let { t: n } = F(), r = I(), i = f(() => String(r.params.user));
		u({ title: () => n("guardian.access.user.title", { user: i.value }) });
		let { data: a } = l("guardian.user", i), c = f(() => t(a.value?.data.options));
		return (e, t) => {
			let r = j, a = s, l = M;
			return b(), p(l, null, {
				header: E(() => [v(r, { title: T(n)("guardian.access.user.title", { user: T(i) }) }, null, 8, ["title"])]),
				body: E(() => [v(a, {
					"screen-key": "guardian.user",
					record: T(i)
				}, {
					default: E(() => [(b(), h("div", {
						key: T(i),
						class: "space-y-4",
						"data-testid": "guardian-user"
					}, [g("div", G, [g("div", K, [v(W, {
						user: T(i),
						"scope-types": T(c)
					}, null, 8, ["user", "scope-types"])]), g("div", q, [v(H, {
						user: T(i),
						"scope-types": T(c)
					}, null, 8, ["user", "scope-types"])])]), v(o, { user: T(i) }, null, 8, ["user"])]))]),
					_: 1
				}, 8, ["record"])]),
				_: 1
			});
		};
	}
});
export { J as default };
