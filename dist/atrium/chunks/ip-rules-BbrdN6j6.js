import { t as e } from "./GuardianErrors-mhgGBfyk.js";
import { t } from "./GuardianPager-DnfHQ8Kk.js";
import { i as n, n as r } from "./access-Ea_ryMId.js";
import { a as i, s as a } from "./admin-_cLX5Zre.js";
import { t as o } from "./GuardianState-DytBr9ih.js";
import { d as s, f as c, i as l, o as u } from "./guardian-BXKFb2X7.js";
import { AtriumIntegrationScreen as d, AtriumState as ee, useAtriumScreen as f, useHead as p } from "@refactor-circus/atrium";
import { computed as m, createBlock as h, createCommentVNode as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as b, openBlock as x, reactive as S, ref as C, toDisplayString as w, unref as T, withCtx as E, withModifiers as D } from "vue";
import { UAlert as O, UBadge as k, UButton as A, UCard as te, UDashboardNavbar as ne, UDashboardPanel as re, UFormField as j, UInput as M, USelect as N, UTable as P } from "@refactor-circus/atrium/ui";
import { useI18n as F } from "vue-i18n";
// src/pages/ip-rules/index.vue?vue&type=script&setup=true&lang.ts
var I = {
	class: "space-y-4",
	"data-testid": "guardian-ip-rules"
}, L = {
	class: "text-sm text-muted",
	"data-testid": "guardian-ip-rules-mode"
}, R = { class: "space-y-4" }, z = ["data-testid"], B = { class: "font-medium" }, V = { class: "mt-3" }, H = /* @__PURE__ */ b({
	__name: "index",
	setup(b) {
		let { t: H } = F(), U = s(), W = s();
		p({ title: () => H("guardian.admin.ip_rules.title") });
		let { data: G } = f("guardian.ip-rules"), K = m(() => a(G.value?.data.options)), q = C(1), J = m(() => ({
			per_page: 25,
			...q.value > 1 ? { page: q.value } : {}
		})), { client: Y, data: X, pending: ie, failed: ae, status: oe, reload: Z } = c(async (e) => u(await e.GET("/ip-rules", { params: { query: J.value } })), J), se = m(() => X.value?.data ?? []), Q = m(() => n(X.value)), ce = m(() => [
			{
				id: "cidr",
				header: H("guardian.admin.ip_rules.fields.cidr"),
				meta: { class: { td: "font-mono" } }
			},
			{
				id: "action",
				header: H("guardian.admin.ip_rules.fields.action")
			},
			{
				id: "description",
				header: H("guardian.admin.ip_rules.fields.description")
			},
			{
				id: "expires_at",
				header: H("guardian.admin.ip_rules.fields.expires_at"),
				meta: { class: { td: "text-muted" } }
			},
			{
				id: "actions",
				meta: { class: { td: "text-right" } }
			}
		]), le = m(() => ["allow", "deny"].map((e) => ({
			label: H(`guardian.admin.ip_rules.actions.${e}`),
			value: e
		}))), $ = S({
			cidr: "",
			action: "deny",
			description: "",
			expires: ""
		});
		async function ue() {
			if (!Y) return;
			let e = {
				cidr: $.cidr.trim(),
				action: $.action,
				description: r($.description),
				expires_at: i($.expires)
			};
			await U.run(async () => u(await Y.POST("/ip-rules", { body: e }))) && (Object.assign($, {
				cidr: "",
				action: "deny",
				description: "",
				expires: ""
			}), await Z());
		}
		async function de(e) {
			Y && window.confirm(H("guardian.admin.ip_rules.confirm_delete", { cidr: e.cidr })) && (K.value !== "allowlist" || e.action !== "allow" || window.confirm(H("guardian.admin.ip_rules.confirm_allow_delete", { cidr: e.cidr }))) && await W.run(async () => u(await Y.DELETE("/ip-rules/{rule}", { params: { path: { rule: e.id } } }))) && await Z();
		}
		function fe(e) {
			q.value = e;
		}
		return (n, r) => {
			let i = ne, a = O, s = k, c = A, u = ee, f = P, p = M, m = j, b = N, S = te, C = d, F = re;
			return x(), h(F, null, {
				header: E(() => [y(i, { title: T(H)("guardian.admin.ip_rules.title") }, null, 8, ["title"])]),
				body: E(() => [y(C, { "screen-key": "guardian.ip-rules" }, {
					default: E(() => [_("div", I, [
						_("p", L, w(T(H)(`guardian.admin.ip_rules.mode.${T(K)}`)), 1),
						T(K) === "allowlist" ? (x(), h(a, {
							key: 0,
							color: "warning",
							variant: "subtle",
							icon: "i-lucide-triangle-alert",
							title: T(H)("guardian.admin.ip_rules.allowlist_warning"),
							"data-testid": "guardian-ip-rules-allowlist"
						}, null, 8, ["title"])) : g("", !0),
						y(o, {
							ready: !T(ie) && T(X) !== null,
							failed: T(ae),
							status: T(oe)
						}, {
							default: E(() => [_("div", R, [
								y(e, {
									action: T(W),
									testid: "guardian-ip-rule-delete"
								}, null, 8, ["action"]),
								y(f, {
									data: T(se),
									columns: T(ce),
									"data-testid": "guardian-ip-rules-table"
								}, {
									"cidr-cell": E(({ row: e }) => [_("span", { "data-testid": `guardian-ip-rule-${e.original.id}` }, w(e.original.cidr), 9, z)]),
									"action-cell": E(({ row: e }) => [y(s, {
										color: e.original.action === "allow" ? "success" : "error",
										variant: "subtle"
									}, {
										default: E(() => [v(w(T(H)(`guardian.admin.ip_rules.actions.${e.original.action === "allow" ? "allow" : "deny"}`)), 1)]),
										_: 2
									}, 1032, ["color"])]),
									"description-cell": E(({ row: e }) => [v(w(e.original.description ?? "—"), 1)]),
									"expires_at-cell": E(({ row: e }) => [v(w(T(l)(e.original.expires_at) ?? T(H)("guardian.admin.never")), 1)]),
									"actions-cell": E(({ row: e }) => [y(c, {
										size: "xs",
										color: "error",
										variant: "ghost",
										icon: "i-lucide-trash",
										"data-testid": `guardian-ip-rule-delete-${e.original.id}`,
										onClick: (t) => de(e.original)
									}, {
										default: E(() => [v(w(T(H)("guardian.admin.delete")), 1)]),
										_: 1
									}, 8, ["data-testid", "onClick"])]),
									empty: E(() => [y(u, {
										compact: "",
										title: T(H)("guardian.admin.ip_rules.empty"),
										"data-testid": "guardian-ip-rules-empty"
									}, null, 8, ["title"])]),
									_: 1
								}, 8, ["data", "columns"]),
								y(t, {
									current: T(Q).current,
									last: T(Q).last,
									testid: "guardian-ip-rules",
									onGo: fe
								}, null, 8, ["current", "last"]),
								y(S, null, {
									header: E(() => [_("h2", B, w(T(H)("guardian.admin.ip_rules.new")), 1)]),
									default: E(() => [_("form", {
										class: "flex flex-wrap items-start gap-3",
										"data-testid": "guardian-ip-rule-form",
										onSubmit: D(ue, ["prevent"])
									}, [
										y(m, {
											label: T(H)("guardian.admin.ip_rules.fields.cidr"),
											hint: T(H)("guardian.admin.ip_rules.cidr_hint"),
											error: T(U).error("cidr"),
											required: ""
										}, {
											default: E(() => [y(p, {
												modelValue: T($).cidr,
												"onUpdate:modelValue": r[0] ||= (e) => T($).cidr = e,
												class: "w-48 font-mono",
												"data-testid": "guardian-ip-rule-cidr"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, [
											"label",
											"hint",
											"error"
										]),
										y(m, {
											label: T(H)("guardian.admin.ip_rules.fields.action"),
											error: T(U).error("action"),
											required: ""
										}, {
											default: E(() => [y(b, {
												modelValue: T($).action,
												"onUpdate:modelValue": r[1] ||= (e) => T($).action = e,
												items: T(le),
												class: "w-32",
												"data-testid": "guardian-ip-rule-action"
											}, null, 8, ["modelValue", "items"])]),
											_: 1
										}, 8, ["label", "error"]),
										y(m, {
											label: T(H)("guardian.admin.ip_rules.fields.description"),
											error: T(U).error("description")
										}, {
											default: E(() => [y(p, {
												modelValue: T($).description,
												"onUpdate:modelValue": r[2] ||= (e) => T($).description = e,
												class: "w-64",
												"data-testid": "guardian-ip-rule-description"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, ["label", "error"]),
										y(m, {
											label: T(H)("guardian.admin.ip_rules.fields.expires_at"),
											error: T(U).error("expires_at")
										}, {
											default: E(() => [y(p, {
												modelValue: T($).expires,
												"onUpdate:modelValue": r[3] ||= (e) => T($).expires = e,
												type: "date",
												"data-testid": "guardian-ip-rule-expires"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, ["label", "error"]),
										y(c, {
											type: "submit",
											icon: "i-lucide-plus",
											loading: T(U).busy.value,
											class: "mt-field-label",
											"data-testid": "guardian-ip-rule-submit"
										}, {
											default: E(() => [v(w(T(H)("guardian.admin.ip_rules.create")), 1)]),
											_: 1
										}, 8, ["loading"])
									], 32), _("div", V, [Object.keys(T(U).errors.value).length === 0 ? (x(), h(e, {
										key: 0,
										action: T(U),
										testid: "guardian-ip-rule-create"
									}, null, 8, ["action"])) : g("", !0)])]),
									_: 1
								})
							])]),
							_: 1
						}, 8, [
							"ready",
							"failed",
							"status"
						])
					])]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { H as default };
