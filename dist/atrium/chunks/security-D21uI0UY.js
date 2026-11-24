import { t as e } from "./GuardianState-DytBr9ih.js";
import { c as t, d as n, f as r, i, l as a, o } from "./guardian-BXKFb2X7.js";
import { t as s } from "./GuardianActionError-DKEMy_E4.js";
import { t as c } from "./GuardianTwoFactorMethods-MqspTIv-.js";
import { AtriumIntegrationScreen as l, useAtriumScreen as u, useHead as d } from "@refactor-circus/atrium";
import { Fragment as f, computed as p, createBlock as m, createCommentVNode as h, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as b, isRef as x, onBeforeUnmount as S, openBlock as C, reactive as w, ref as T, renderList as E, toDisplayString as D, unref as O, withCtx as k, withModifiers as A } from "vue";
import { UAlert as j, UBadge as M, UButton as N, UCard as P, UCheckbox as F, UDashboardNavbar as I, UDashboardPanel as L, UFormField as R, UInput as ee } from "@refactor-circus/atrium/ui";
import { useI18n as z } from "vue-i18n";
// src/components/security/security.ts
var B = {
	tokenAbilities: ["*"],
	tokenMaxLifetimeDays: 365
};
function V(e) {
	let t = Array.isArray(e?.token_abilities) ? e.token_abilities.filter((e) => typeof e == "string" && e !== "") : [], n = e?.token_max_lifetime_days;
	return {
		tokenAbilities: t.length > 0 ? t : B.tokenAbilities,
		tokenMaxLifetimeDays: n === null ? null : typeof n == "number" && n > 0 ? n : B.tokenMaxLifetimeDays
	};
}
function H(e) {
	let t = (e) => String(e).padStart(2, "0");
	return `${e.getFullYear()}-${t(e.getMonth() + 1)}-${t(e.getDate())}`;
}
function te(e, t = /* @__PURE__ */ new Date()) {
	if (e === null) return null;
	let n = new Date(t);
	return n.setDate(n.getDate() + e), H(n);
}
function ne(e) {
	return (/* @__PURE__ */ new Date(`${e}T23:59:59`)).toISOString();
}
// src/components/security/SecurityActivity.vue?vue&type=script&setup=true&lang.ts
var U = { class: "font-semibold" }, W = { class: "divide-y divide-default text-sm" }, G = ["data-testid"], K = { class: "font-mono" }, q = { class: "text-muted" }, J = {
	key: 0,
	class: "py-2 text-muted"
}, Y = { class: "mt-3 flex gap-2" }, X = /* @__PURE__ */ b({
	__name: "SecurityActivity",
	setup(t) {
		let { t: n } = z(), a = T(null), s = T([]), { data: c, pending: l, failed: u, status: d } = r(async (e) => o(await e.GET("/me/security-events", { params: { query: {
			per_page: 50,
			...a.value ? { cursor: a.value } : {}
		} } })), a);
		function p() {
			let e = c.value?.meta?.next_cursor;
			e && (s.value = [...s.value, a.value], a.value = e);
		}
		function b() {
			a.value = s.value.at(-1) ?? null, s.value = s.value.slice(0, -1);
		}
		return (t, r) => {
			let a = N, o = P;
			return C(), m(o, { "data-testid": "guardian-security-activity" }, {
				header: k(() => [_("h2", U, D(O(n)("guardian.security.activity.title")), 1)]),
				default: k(() => [y(e, {
					ready: !O(l) && O(c) !== null,
					failed: O(u),
					status: O(d),
					compact: ""
				}, {
					default: k(() => [_("ul", W, [(C(!0), g(f, null, E(O(c)?.data ?? [], (e) => (C(), g("li", {
						key: e.id,
						class: "flex flex-wrap justify-between gap-2 py-2",
						"data-testid": `guardian-activity-${e.id}`
					}, [_("span", K, D(e.type), 1), _("span", q, D(e.ip ?? "—") + " · " + D(O(i)(e.occurred_at)), 1)], 8, G))), 128)), (O(c)?.data ?? []).length === 0 ? (C(), g("li", J, D(O(n)("guardian.security.activity.none")), 1)) : h("", !0)]), _("div", Y, [O(s).length > 0 ? (C(), m(a, {
						key: 0,
						size: "xs",
						color: "neutral",
						variant: "soft",
						"data-testid": "guardian-activity-newer",
						onClick: b
					}, {
						default: k(() => [v(D(O(n)("guardian.security.activity.newer")), 1)]),
						_: 1
					})) : h("", !0), O(c)?.meta?.next_cursor ? (C(), m(a, {
						key: 1,
						size: "xs",
						color: "neutral",
						variant: "soft",
						"data-testid": "guardian-activity-older",
						onClick: p
					}, {
						default: k(() => [v(D(O(n)("guardian.security.activity.older")), 1)]),
						_: 1
					})) : h("", !0)])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			});
		};
	}
}), Z = { class: "font-semibold" }, Q = { class: "divide-y divide-default text-sm" }, re = ["data-testid"], ie = { class: "text-muted" }, ae = {
	key: 0,
	class: "py-2 text-muted"
}, oe = /* @__PURE__ */ b({
	__name: "SecurityDevices",
	setup(t) {
		let { t: a } = z(), c = n(), { client: l, data: u, pending: d, failed: p, status: b, reload: x } = r(async (e) => o(await e.GET("/devices", { params: { query: { per_page: 50 } } })).data);
		async function S(e) {
			l && window.confirm(a("guardian.security.devices.confirm_forget")) && await c.run(async () => o(await l.DELETE("/devices/{device}", { params: { path: { device: e.id } } }))) && await x();
		}
		return (t, n) => {
			let r = N, o = P;
			return C(), m(o, { "data-testid": "guardian-security-devices" }, {
				header: k(() => [_("h2", Z, D(O(a)("guardian.security.devices.title")), 1)]),
				default: k(() => [y(e, {
					ready: !O(d) && O(u) !== null,
					failed: O(p),
					status: O(b),
					compact: ""
				}, {
					default: k(() => [y(s, { action: O(c) }, null, 8, ["action"]), _("ul", Q, [(C(!0), g(f, null, E(O(u) ?? [], (e) => (C(), g("li", {
						key: e.id,
						class: "flex flex-wrap items-center justify-between gap-2 py-2",
						"data-testid": `guardian-device-${e.id}`
					}, [_("span", null, [_("strong", null, D(e.name ?? O(a)("guardian.security.devices.unnamed")), 1), _("span", ie, " · " + D(e.last_ip ?? "—") + " · " + D(O(i)(e.last_seen_at)), 1)]), y(r, {
						size: "xs",
						color: "error",
						variant: "ghost",
						icon: "i-lucide-trash-2",
						"data-testid": `guardian-device-forget-${e.id}`,
						onClick: (t) => S(e)
					}, {
						default: k(() => [v(D(O(a)("guardian.security.devices.forget")), 1)]),
						_: 1
					}, 8, ["data-testid", "onClick"])], 8, re))), 128)), (O(u) ?? []).length === 0 ? (C(), g("li", ae, D(O(a)("guardian.security.devices.none")), 1)) : h("", !0)])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			});
		};
	}
}), se = { class: "font-semibold" }, ce = { class: "space-y-3" }, le = { class: "divide-y divide-default text-sm" }, $ = ["data-testid"], ue = { class: "text-muted" }, de = { class: "flex flex-wrap items-center gap-3" }, fe = /* @__PURE__ */ b({
	__name: "SecuritySessions",
	setup(t) {
		let { t: c } = z(), l = n(), u = T(!1), d = T(null), { client: p, data: b, pending: S, failed: w, status: A, reload: I } = r(async (e) => o(await e.GET("/sessions", { params: { query: { per_page: 50 } } })).data);
		async function L(e) {
			p && window.confirm(c("guardian.security.sessions.confirm_revoke")) && await l.run(async () => o(await p.DELETE("/sessions/{session}", { params: { path: { session: e.id } } }))) && await I();
		}
		async function R() {
			if (!p || !window.confirm(c(u.value ? "guardian.security.sessions.confirm_others_tokens" : "guardian.security.sessions.confirm_others"))) return;
			d.value = null;
			let e = [];
			await l.run(async () => {
				e.push(o(await p.DELETE("/sessions/others", u.value ? { body: a({ tokens: !0 }) } : {})));
			}) && e[0] && (d.value = c("guardian.security.sessions.revoked", {
				count: e[0].revoked,
				tokens: e[0].tokens_revoked ?? 0
			}), await I());
		}
		return (t, n) => {
			let r = j, a = M, o = N, p = F, T = P;
			return C(), m(T, { "data-testid": "guardian-security-sessions" }, {
				header: k(() => [_("h2", se, D(O(c)("guardian.security.sessions.title")), 1)]),
				default: k(() => [y(e, {
					ready: !O(S) && O(b) !== null,
					failed: O(w),
					status: O(A),
					compact: ""
				}, {
					default: k(() => [_("div", ce, [
						O(d) ? (C(), m(r, {
							key: 0,
							color: "success",
							variant: "subtle",
							title: O(d),
							"data-testid": "guardian-sessions-notice"
						}, null, 8, ["title"])) : h("", !0),
						y(s, { action: O(l) }, null, 8, ["action"]),
						_("ul", le, [(C(!0), g(f, null, E(O(b) ?? [], (e) => (C(), g("li", {
							key: e.id,
							class: "flex flex-wrap items-center justify-between gap-2 py-2",
							"data-testid": `guardian-session-${e.id}`
						}, [_("span", null, [
							_("strong", null, D(e.device_name ?? e.user_agent ?? O(c)("guardian.security.sessions.unknown")), 1),
							_("span", ue, " · " + D(e.ip ?? "—") + " · " + D(O(i)(e.last_active_at)), 1),
							e.current ? (C(), m(a, {
								key: 0,
								color: "primary",
								variant: "subtle",
								class: "ml-2",
								"data-testid": "guardian-session-current"
							}, {
								default: k(() => [v(D(O(c)("guardian.security.sessions.current")), 1)]),
								_: 1
							})) : h("", !0)
						]), e.current ? h("", !0) : (C(), m(o, {
							key: 0,
							size: "xs",
							color: "error",
							variant: "ghost",
							icon: "i-lucide-log-out",
							"data-testid": `guardian-session-revoke-${e.id}`,
							onClick: (t) => L(e)
						}, {
							default: k(() => [v(D(O(c)("guardian.security.sessions.revoke")), 1)]),
							_: 1
						}, 8, ["data-testid", "onClick"]))], 8, $))), 128))]),
						_("div", de, [y(p, {
							modelValue: O(u),
							"onUpdate:modelValue": n[0] ||= (e) => x(u) ? u.value = e : null,
							label: O(c)("guardian.security.sessions.also_tokens"),
							"data-testid": "guardian-sessions-also-tokens"
						}, null, 8, ["modelValue", "label"]), y(o, {
							color: "error",
							variant: "soft",
							icon: "i-lucide-log-out",
							loading: O(l).busy.value,
							"data-testid": "guardian-sessions-others",
							onClick: R
						}, {
							default: k(() => [v(D(O(c)("guardian.security.sessions.others")), 1)]),
							_: 1
						}, 8, ["loading"])])
					])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			});
		};
	}
}), pe = { class: "font-semibold" }, me = { class: "space-y-4" }, he = {
	class: "break-all font-mono",
	"data-testid": "guardian-token-plain-value"
}, ge = { class: "divide-y divide-default text-sm" }, _e = ["data-testid"], ve = { class: "text-muted" }, ye = {
	key: 0,
	class: "py-2 text-muted"
}, be = { class: "flex flex-wrap gap-3" }, xe = /* @__PURE__ */ b({
	__name: "SecurityTokens",
	props: {
		abilities: {},
		maxDays: {}
	},
	setup(c) {
		let l = c, { t: u } = z(), d = n(), b = n(), x = w({
			name: "",
			abilities: [...l.abilities],
			expires: ""
		}), M = T(null), I = T(!1), L = T(null), B = p(() => te(l.maxDays)), { client: V, data: H, pending: U, failed: W, status: G, reload: K } = r(async (e) => o(await e.GET("/tokens")).data), q = p(() => W.value && G.value === 501);
		function J(e, n) {
			x.abilities = t(x.abilities, e, n);
		}
		async function Y() {
			if (L.value = null, !V) return;
			if (x.expires && B.value && x.expires > B.value) {
				L.value = u("guardian.security.tokens.expiry_too_late", { days: l.maxDays ?? 0 });
				return;
			}
			let e = null;
			await d.run(async () => {
				e = o(await V.POST("/tokens", { body: a({
					name: x.name.trim(),
					abilities: x.abilities,
					expires_at: x.expires ? ne(x.expires) : null
				}) })).plain_text_token;
			}) && (M.value = e, I.value = !1, x.name = "", x.expires = "", await K());
		}
		async function X() {
			M.value && (await navigator.clipboard?.writeText(M.value), I.value = !0);
		}
		async function Z(e) {
			V && window.confirm(u("guardian.security.tokens.confirm_revoke", { name: e.name })) && await b.run(async () => o(await V.DELETE("/tokens/{token}", { params: { path: { token: e.id } } }))) && await K();
		}
		function Q() {
			M.value = null;
		}
		return S(Q), (t, n) => {
			let r = j, a = N, o = ee, l = R, p = F, S = P;
			return C(), m(S, { "data-testid": "guardian-security-tokens" }, {
				header: k(() => [_("h2", pe, D(O(u)("guardian.security.tokens.title")), 1)]),
				default: k(() => [O(q) ? (C(), m(r, {
					key: 0,
					color: "neutral",
					variant: "subtle",
					icon: "i-lucide-key-round",
					title: O(u)("guardian.security.tokens.not_enabled"),
					"data-testid": "guardian-tokens-disabled"
				}, null, 8, ["title"])) : (C(), m(e, {
					key: 1,
					ready: !O(U) && O(H) !== null,
					failed: O(W),
					status: O(G),
					compact: ""
				}, {
					default: k(() => [_("div", me, [
						O(M) ? (C(), m(r, {
							key: 0,
							color: "success",
							variant: "subtle",
							title: O(u)("guardian.security.tokens.plain_once"),
							"data-testid": "guardian-token-plain"
						}, {
							description: k(() => [_("code", he, D(O(M)), 1)]),
							actions: k(() => [y(a, {
								size: "xs",
								icon: "i-lucide-copy",
								color: "neutral",
								variant: "soft",
								"data-testid": "guardian-token-copy",
								onClick: X
							}, {
								default: k(() => [v(D(O(I) ? O(u)("guardian.security.tokens.copied") : O(u)("guardian.security.tokens.copy")), 1)]),
								_: 1
							}), y(a, {
								size: "xs",
								color: "neutral",
								variant: "ghost",
								"data-testid": "guardian-token-hide",
								onClick: Q
							}, {
								default: k(() => [v(D(O(u)("guardian.security.tokens.done")), 1)]),
								_: 1
							})]),
							_: 1
						}, 8, ["title"])) : h("", !0),
						y(s, { action: O(b) }, null, 8, ["action"]),
						_("ul", ge, [(C(!0), g(f, null, E(O(H) ?? [], (e) => (C(), g("li", {
							key: e.id,
							class: "flex flex-wrap items-center justify-between gap-2 py-2",
							"data-testid": `guardian-token-${e.id}`
						}, [_("span", null, [_("strong", null, D(e.name), 1), _("span", ve, " · " + D(e.abilities.join(", ")) + " · " + D(e.expires_at ? O(u)("guardian.security.tokens.expires", { date: O(i)(e.expires_at) }) : O(u)("guardian.security.tokens.no_expiry")), 1)]), y(a, {
							size: "xs",
							color: "error",
							variant: "ghost",
							icon: "i-lucide-trash-2",
							"data-testid": `guardian-token-revoke-${e.id}`,
							onClick: (t) => Z(e)
						}, {
							default: k(() => [v(D(O(u)("guardian.security.tokens.revoke")), 1)]),
							_: 1
						}, 8, ["data-testid", "onClick"])], 8, _e))), 128)), (O(H) ?? []).length === 0 ? (C(), g("li", ye, D(O(u)("guardian.security.tokens.none")), 1)) : h("", !0)]),
						y(s, { action: O(d) }, null, 8, ["action"]),
						_("form", {
							class: "space-y-3",
							"data-testid": "guardian-token-form",
							onSubmit: A(Y, ["prevent"])
						}, [
							y(l, {
								label: O(u)("guardian.security.tokens.name"),
								error: O(d).error("name"),
								required: ""
							}, {
								default: k(() => [y(o, {
									modelValue: O(x).name,
									"onUpdate:modelValue": n[0] ||= (e) => O(x).name = e,
									"data-testid": "guardian-token-name"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							y(l, {
								label: O(u)("guardian.security.tokens.abilities"),
								error: O(d).error("abilities")
							}, {
								default: k(() => [_("div", be, [(C(!0), g(f, null, E(c.abilities, (e) => (C(), m(p, {
									key: e,
									"model-value": O(x).abilities.includes(e),
									label: e === "*" ? O(u)("guardian.security.tokens.every_ability") : e,
									"data-testid": `guardian-token-ability-${e}`,
									"onUpdate:modelValue": (t) => J(e, t)
								}, null, 8, [
									"model-value",
									"label",
									"data-testid",
									"onUpdate:modelValue"
								]))), 128))])]),
								_: 1
							}, 8, ["label", "error"]),
							y(l, {
								label: O(u)("guardian.security.tokens.expires_at"),
								hint: O(B) ? O(u)("guardian.security.tokens.expiry_hint", { date: O(B) }) : void 0,
								error: O(L) ?? O(d).error("expires_at")
							}, {
								default: k(() => [y(o, {
									modelValue: O(x).expires,
									"onUpdate:modelValue": n[1] ||= (e) => O(x).expires = e,
									type: "date",
									max: O(B) ?? void 0,
									"data-testid": "guardian-token-expires"
								}, null, 8, ["modelValue", "max"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							y(a, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: O(d).busy.value,
								"data-testid": "guardian-token-submit"
							}, {
								default: k(() => [v(D(O(u)("guardian.security.tokens.issue")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32)
					])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				]))]),
				_: 1
			});
		};
	}
}), Se = { class: "font-semibold" }, Ce = {
	key: 0,
	class: "space-y-4"
}, we = { class: "flex flex-wrap items-center gap-2 text-sm" }, Te = { key: 0 }, Ee = {
	key: 1,
	"data-testid": "guardian-security-two-factor-deadline"
}, De = /* @__PURE__ */ b({
	__name: "SecurityTwoFactor",
	setup(t) {
		let { t: n } = z(), { client: a, data: s, pending: l, failed: u, status: d, reload: f } = r(async (e) => o(await e.GET("/two-factor")).data);
		return (t, r) => {
			let o = M, p = P;
			return C(), m(p, { "data-testid": "guardian-security-two-factor" }, {
				header: k(() => [_("h2", Se, D(O(n)("guardian.security.two_factor.title")), 1)]),
				default: k(() => [y(e, {
					ready: !O(l) && O(s) !== null,
					failed: O(u),
					status: O(d),
					compact: ""
				}, {
					default: k(() => [O(s) && O(a) ? (C(), g("div", Ce, [_("div", we, [
						y(o, {
							color: O(s).enabled ? "success" : "neutral",
							variant: "subtle",
							"data-testid": "guardian-security-two-factor-state"
						}, {
							default: k(() => [v(D(O(s).enabled ? O(n)("guardian.security.two_factor.on") : O(n)("guardian.security.two_factor.off")), 1)]),
							_: 1
						}, 8, ["color"]),
						O(s).required ? (C(), g("span", Te, D(O(n)("guardian.security.two_factor.required")), 1)) : h("", !0),
						O(s).enrolment_deadline ? (C(), g("span", Ee, D(O(n)("guardian.security.two_factor.deadline", { deadline: O(i)(O(s).enrolment_deadline) ?? O(s).enrolment_deadline })), 1)) : h("", !0)
					]), y(c, {
						client: O(a),
						status: O(s),
						onChanged: O(f)
					}, null, 8, [
						"client",
						"status",
						"onChanged"
					])])) : h("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			});
		};
	}
}), Oe = { class: "grid gap-4 lg:grid-cols-2" }, ke = { class: "lg:col-span-2" }, Ae = {
	key: 0,
	class: "lg:col-span-2"
}, je = { class: "lg:col-span-2" }, Me = /* @__PURE__ */ b({
	__name: "index",
	setup(e) {
		let { t } = z();
		d({ title: () => t("guardian.security.title") });
		let { data: n, status: r } = u("guardian.security"), i = p(() => r.value === "success" || r.value === "error"), a = p(() => V(n.value?.data.options));
		return (e, n) => {
			let r = I, o = l, s = L;
			return C(), m(s, null, {
				header: k(() => [y(r, { title: O(t)("guardian.security.title") }, null, 8, ["title"])]),
				body: k(() => [y(o, { "screen-key": "guardian.security" }, {
					default: k(() => [_("div", Oe, [
						_("div", ke, [y(De)]),
						y(fe),
						y(oe),
						O(i) ? (C(), g("div", Ae, [(C(), m(xe, {
							key: O(a).tokenAbilities.join("|"),
							abilities: O(a).tokenAbilities,
							"max-days": O(a).tokenMaxLifetimeDays
						}, null, 8, ["abilities", "max-days"]))])) : h("", !0),
						_("div", je, [y(X)])
					])]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { Me as default };
