import { n as e, s as t } from "./access-CMUXpA7X.js";
import { t as n } from "./GuardianState-DytBr9ih.js";
import { d as r, o as i } from "./guardian-D9MeowUD.js";
import { i as a, n as o, t as s } from "./ui-BHDIeg3q.js";
import { t as c } from "./GuardianAssignments-BWA4wz2w.js";
import { t as l } from "./UserSecurity-CIxorfVg.js";
import { AtriumIntegrationScreen as u, useAtriumScreen as d, useHead as f } from "@refactor-circus/atrium";
import { Fragment as p, computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, openBlock as S, reactive as C, ref as w, renderList as T, toDisplayString as E, unref as D, withCtx as O, withModifiers as k } from "vue";
import { UBadge as A, UButton as j, UCard as M, UDashboardNavbar as N, UDashboardPanel as P, UFormField as F, UInput as I, USelect as L } from "@refactor-circus/atrium/ui";
import { useI18n as R } from "vue-i18n";
import { useRoute as z } from "vue-router";
// src/components/users/UserAbilities.vue?vue&type=script&setup=true&lang.ts
var B = { class: "font-semibold" }, V = { class: "space-y-3" }, H = {
	key: 0,
	"data-testid": "guardian-abilities-super"
}, U = {
	key: 1,
	class: "text-sm text-muted"
}, W = {
	key: 2,
	class: "flex flex-wrap gap-1",
	"data-testid": "guardian-abilities-list"
}, G = /* @__PURE__ */ x({
	__name: "UserAbilities",
	props: {
		user: {},
		scopeTypes: {}
	},
	setup(t) {
		let c = t, { t: l } = R(), u = C({
			scope_type: "",
			scope_id: ""
		}), d = w({
			scope_type: null,
			scope_id: null
		}), f = m(() => ({
			user: c.user,
			...d.value
		})), { data: x, pending: N, failed: P, status: z } = r(async (e) => i(await e.GET("/users/{user}/abilities", { params: {
			path: { user: c.user },
			query: d.value.scope_type ? {
				scope_type: d.value.scope_type,
				scope_id: d.value.scope_id
			} : {}
		} })).data, f), G = m(() => [{
			label: l("guardian.access.scopes.global"),
			value: s
		}, ...c.scopeTypes.map((e) => ({
			label: e.key,
			value: e.type
		}))]);
		function K() {
			let t = e(u.scope_type);
			d.value = {
				scope_type: t,
				scope_id: t === null ? null : e(u.scope_id)
			};
		}
		return (e, t) => {
			let r = L, i = F, s = I, c = j, d = A, f = M;
			return S(), h(f, { "data-testid": "guardian-user-abilities" }, {
				header: O(() => [v("h2", B, E(D(l)("guardian.access.user.abilities")), 1)]),
				default: O(() => [v("div", V, [v("form", {
					class: "flex flex-wrap items-end gap-3",
					"data-testid": "guardian-abilities-scope",
					onSubmit: k(K, ["prevent"])
				}, [
					b(i, { label: D(l)("guardian.access.assignments.fields.scope_type") }, {
						default: O(() => [b(r, {
							"model-value": D(o)(D(u).scope_type),
							items: D(G),
							class: "w-44",
							"data-testid": "guardian-abilities-scope-type",
							"onUpdate:modelValue": t[0] ||= (e) => D(u).scope_type = D(a)(e)
						}, null, 8, ["model-value", "items"])]),
						_: 1
					}, 8, ["label"]),
					D(u).scope_type ? (S(), h(i, {
						key: 0,
						label: D(l)("guardian.access.assignments.fields.scope_id")
					}, {
						default: O(() => [b(s, {
							modelValue: D(u).scope_id,
							"onUpdate:modelValue": t[1] ||= (e) => D(u).scope_id = e,
							class: "w-32",
							"data-testid": "guardian-abilities-scope-id"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, ["label"])) : g("", !0),
					b(c, {
						type: "submit",
						size: "sm",
						variant: "soft",
						"data-testid": "guardian-abilities-apply"
					}, {
						default: O(() => [y(E(D(l)("guardian.access.user.show_abilities")), 1)]),
						_: 1
					})
				], 32), b(n, {
					ready: !D(N) && D(x) !== null,
					failed: D(P),
					status: D(z),
					compact: ""
				}, {
					default: O(() => [D(x)?.super_admin ? (S(), _("p", H, E(D(l)("guardian.access.user.super_admin")), 1)) : (D(x)?.abilities ?? []).length === 0 ? (S(), _("p", U, E(D(l)("guardian.access.user.no_abilities")), 1)) : (S(), _("ul", W, [(S(!0), _(p, null, T(D(x)?.abilities ?? [], (e) => (S(), _("li", { key: e }, [b(d, {
						color: "neutral",
						variant: "subtle"
					}, {
						default: O(() => [v("code", null, E(e), 1)]),
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
}), K = { class: "font-semibold" }, q = /* @__PURE__ */ x({
	__name: "UserAssignments",
	props: {
		user: {},
		scopeTypes: {}
	},
	setup(e) {
		let { t } = R();
		return (n, r) => {
			let i = M;
			return S(), h(i, { "data-testid": "guardian-user-assignments" }, {
				header: O(() => [v("h2", K, E(D(t)("guardian.access.user.assignments")), 1)]),
				default: O(() => [b(c, {
					user: e.user,
					"scope-types": e.scopeTypes
				}, null, 8, ["user", "scope-types"])]),
				_: 1
			});
		};
	}
}), J = { class: "grid gap-4 lg:grid-cols-2" }, Y = { class: "lg:col-span-2" }, X = { class: "lg:col-span-2" }, Z = /* @__PURE__ */ x({
	__name: "[user]",
	setup(e) {
		let { t: n } = R(), r = z(), i = m(() => String(r.params.user));
		f({ title: () => n("guardian.access.user.title", { user: i.value }) });
		let { data: a } = d("guardian.user", i), o = m(() => t(a.value?.data.options));
		return (e, t) => {
			let r = N, a = u, s = P;
			return S(), h(s, null, {
				header: O(() => [b(r, { title: D(n)("guardian.access.user.title", { user: D(i) }) }, null, 8, ["title"])]),
				body: O(() => [b(a, {
					"screen-key": "guardian.user",
					record: D(i)
				}, {
					default: O(() => [(S(), _("div", {
						key: D(i),
						class: "space-y-4",
						"data-testid": "guardian-user"
					}, [v("div", J, [v("div", Y, [b(q, {
						user: D(i),
						"scope-types": D(o)
					}, null, 8, ["user", "scope-types"])]), v("div", X, [b(G, {
						user: D(i),
						"scope-types": D(o)
					}, null, 8, ["user", "scope-types"])])]), b(l, { user: D(i) }, null, 8, ["user"])]))]),
					_: 1
				}, 8, ["record"])]),
				_: 1
			});
		};
	}
});
export { Z as default };
