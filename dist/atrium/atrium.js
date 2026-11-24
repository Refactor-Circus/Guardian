import { c as e, d as t, i as n, l as r, n as i, o as a, t as o, u as s } from "./chunks/guardian-D9MeowUD.js";
import { t as c } from "./chunks/GuardianAssignments-BWA4wz2w.js";
import { i as l, s as u } from "./chunks/access-CMUXpA7X.js";
import { t as d } from "./chunks/GuardianErrors-DtyToZz8.js";
import { t as f } from "./chunks/GuardianPager-DnfHQ8Kk.js";
import { t as p } from "./chunks/GuardianState-DytBr9ih.js";
import { i as m, n as h, t as g } from "./chunks/ui-BHDIeg3q.js";
import { c as _, i as v, r as y, s as b, t as x } from "./chunks/SsoConnectionFields-BBomoNqB.js";
import { t as S } from "./chunks/UserSecurity-CIxorfVg.js";
import { AtriumNoAccess as C, AtriumState as ee, atriumRouter as w, defineAtriumModule as T, mergeAtriumMessages as E, navigateTo as D, useAsyncData as O, useAtrium as k, useAtriumApi as A, useAtriumIntegration as te } from "@refactor-circus/atrium";
import { Fragment as j, computed as M, createBlock as N, createCommentVNode as P, createElementBlock as F, createElementVNode as I, createTextVNode as L, createVNode as R, defineComponent as z, onBeforeUnmount as ne, openBlock as B, reactive as V, ref as H, renderList as U, shallowRef as re, toDisplayString as W, unref as G, withCtx as K, withModifiers as ie } from "vue";
import { UAlert as ae, UBadge as oe, UButton as q, UCard as J, UFormField as se, UInput as ce, ULink as Y, USelect as le, UTable as X } from "@refactor-circus/atrium/ui";
import { useI18n as Z } from "vue-i18n";
// src/components/two-factor/handler.ts
var ue = "guardian.two-factor", de = "Guardian-Two-Factor-Deadline", fe = "guardian.two-factor-deadline", pe = {
	two_factor_challenge_required: o,
	two_factor_enrollment_required: i
}, Q = !1;
function me(e) {
	return e === "/guardian/two-factor/challenge" || e === "/guardian/two-factor/setup";
}
async function he(e) {
	if (!(e.headers.get("content-type") ?? "").includes("json")) return null;
	try {
		let t = await e.json();
		return typeof t?.code == "string" ? t.code : null;
	} catch {
		return null;
	}
}
function ge(e) {
	return async (t) => {
		let r = w(), i = t.headers.get(de);
		if (i && e.notify(fe, {
			id: i,
			message: "guardian.two_factor.deadline.notice",
			params: { deadline: n(i) ?? i },
			link: {
				to: "/guardian/security",
				label: "guardian.two_factor.deadline.link"
			},
			color: "warning"
		}), t.status !== 403 || Q || me(r.currentRoute.value.path)) return;
		let a = pe[await he(t) ?? ""] ?? null;
		if (!(a === null || Q || me(r.currentRoute.value.path))) {
			Q = !0;
			try {
				await r.push({
					path: a,
					query: { redirect: r.currentRoute.value.fullPath }
				});
			} finally {
				Q = !1;
			}
		}
	};
}
// src/components/hooks/hooks.ts
function $(e) {
	return typeof e == "number" && Number.isFinite(e) ? String(e) : typeof e == "string" && e !== "" ? e : null;
}
function _e(e) {
	return typeof e == "string" && e !== "" ? e : null;
}
function ve(e, t) {
	let n = $(t);
	if (n === null) return null;
	let r = _e(e.scope_type);
	return r === null ? { user: n } : {
		scopeType: r,
		scopeId: n
	};
}
// src/components/hooks/GuardianAccessTab.vue?vue&type=script&setup=true&lang.ts
var ye = {
	key: 0,
	"data-testid": "hooks-guardian-access"
}, be = /* @__PURE__ */ z({
	__name: "GuardianAccessTab",
	props: {
		data: {},
		record: {}
	},
	setup(e) {
		let t = e, n = A(), i = r(), a = M(() => ve(t.data, t.record)), { data: o } = O("guardian-hook-scope-types", async () => {
			try {
				return await n("/screens/guardian.role-assignments");
			} catch {
				return null;
			}
		}, { server: !1 }), s = M(() => u(o.value?.data.options));
		return (e, t) => G(a) && G(i) ? (B(), F("div", ye, ["user" in G(a) ? (B(), N(c, {
			key: G(a).user,
			user: G(a).user,
			"scope-types": G(s)
		}, null, 8, ["user", "scope-types"])) : (B(), N(c, {
			key: `${G(a).scopeType}:${G(a).scopeId}`,
			"scope-type": G(a).scopeType,
			"scope-id": G(a).scopeId,
			"scope-types": G(s)
		}, null, 8, [
			"scope-type",
			"scope-id",
			"scope-types"
		]))])) : P("", !0);
	}
}), xe = {
	class: "space-y-4",
	"data-testid": "guardian-scim-tokens"
}, Se = { class: "flex flex-wrap items-center gap-2" }, Ce = {
	class: "break-all font-mono text-sm",
	"data-testid": "guardian-scim-plain-token"
}, we = { class: "font-medium" }, Te = {
	class: "break-all font-mono text-sm",
	"data-testid": "guardian-scim-base-url"
}, Ee = { class: "space-y-4" }, De = ["data-testid"], Oe = { class: "font-medium" }, ke = { class: "mt-3" }, Ae = /* @__PURE__ */ z({
	__name: "GuardianScimTokens",
	props: {
		organization: {},
		scimPath: { default: null },
		client: { default: void 0 }
	},
	setup(e) {
		let r = e, { t: i } = Z(), o = s(), c = s(), { apiBase: u } = te("guardian"), _ = M(() => b(u, r.scimPath, r.organization)), v = H(1), y = M(() => ({
			per_page: 25,
			...v.value > 1 ? { page: v.value } : {}
		})), { client: x, data: S, pending: C, failed: w, status: T, reload: E } = t(async (e) => a(await e.GET("/organizations/{organization}/scim-tokens", { params: {
			path: { organization: r.organization },
			query: y.value
		} })), () => [r.organization, y.value], r.client === void 0 ? void 0 : r.client), D = re([]);
		x && x.GET("/organizations/{organization}/sso-connections", { params: {
			path: { organization: r.organization },
			query: { per_page: 100 }
		} }).then((e) => D.value = a(e).data).catch(() => D.value = []);
		let O = M(() => S.value?.data ?? []), k = M(() => [
			{
				id: "name",
				header: i("guardian.sso.fields.name")
			},
			{
				id: "connection",
				header: i("guardian.sso.scim.connection")
			},
			{
				id: "last_used",
				header: i("guardian.sso.scim.last_used"),
				meta: { class: { td: "text-muted" } }
			},
			{
				id: "expires",
				header: i("guardian.sso.scim.expires"),
				meta: { class: { td: "text-muted" } }
			},
			{
				id: "actions",
				meta: { class: { td: "text-right" } }
			}
		]), A = M(() => [{
			label: i("guardian.sso.scim.no_connection"),
			value: g
		}, ...D.value.map((e) => ({
			label: e.name,
			value: e.slug
		}))]), j = M(() => l(S.value)), z = V({
			name: "",
			expires_in_days: "",
			sso_connection: ""
		}), U = H(null), Y = H(!1);
		ne(() => {
			U.value = null;
		});
		function ue(e) {
			let t = Number(e);
			return e === "" || !Number.isFinite(t) ? null : Math.trunc(t);
		}
		async function de() {
			if (!x) return;
			U.value = null, Y.value = !1;
			let e = { token: null };
			await o.run(async () => {
				e.token = a(await x.POST("/organizations/{organization}/scim-tokens", {
					params: { path: { organization: r.organization } },
					body: {
						name: z.name.trim(),
						expires_in_days: ue(z.expires_in_days),
						sso_connection: z.sso_connection === "" ? null : z.sso_connection
					}
				})).token;
			}) && (U.value = e.token, Object.assign(z, {
				name: "",
				expires_in_days: "",
				sso_connection: ""
			}), await E());
		}
		async function fe() {
			U.value && (await navigator.clipboard?.writeText(U.value), Y.value = !0);
		}
		async function pe(e) {
			x && window.confirm(i("guardian.sso.scim.confirm_revoke", { name: e.name })) && await c.run(async () => a(await x.DELETE("/scim-tokens/{token}", { params: { path: { token: e.id } } }))) && await E();
		}
		function Q(e) {
			v.value = e;
		}
		return (e, t) => {
			let r = q, a = ae, s = J, l = oe, u = ee, g = X, v = ce, y = se, b = le;
			return B(), F("div", xe, [
				G(U) ? (B(), N(a, {
					key: 0,
					color: "warning",
					variant: "subtle",
					title: G(i)("guardian.sso.scim.created"),
					"data-testid": "guardian-scim-plain"
				}, {
					description: K(() => [I("div", Se, [I("code", Ce, W(G(U)), 1), R(r, {
						size: "xs",
						variant: "soft",
						icon: "i-lucide-copy",
						"data-testid": "guardian-scim-copy",
						onClick: fe
					}, {
						default: K(() => [L(W(G(Y) ? G(i)("guardian.sso.copied") : G(i)("guardian.sso.copy")), 1)]),
						_: 1
					})])]),
					_: 1
				}, 8, ["title"])) : P("", !0),
				R(s, null, {
					header: K(() => [I("h3", we, W(G(i)("guardian.sso.scim.base_url")), 1)]),
					default: K(() => [I("code", Te, W(G(_) ?? "—"), 1)]),
					_: 1
				}),
				R(p, {
					ready: !G(C) && G(S) !== null,
					failed: G(w),
					status: G(T),
					compact: ""
				}, {
					default: K(() => [I("div", Ee, [
						R(d, {
							action: G(c),
							testid: "guardian-scim-revoke"
						}, null, 8, ["action"]),
						R(g, {
							data: G(O),
							columns: G(k)
						}, {
							"name-cell": K(({ row: e }) => [I("div", {
								class: "flex flex-wrap items-center gap-2",
								"data-testid": `guardian-scim-token-${e.original.id}`
							}, [L(W(e.original.name) + " ", 1), e.original.revoked_at ? (B(), N(l, {
								key: 0,
								color: "neutral",
								variant: "subtle"
							}, {
								default: K(() => [L(W(G(i)("guardian.sso.scim.revoked")), 1)]),
								_: 1
							})) : e.original.usable ? P("", !0) : (B(), N(l, {
								key: 1,
								color: "neutral",
								variant: "subtle"
							}, {
								default: K(() => [L(W(G(i)("guardian.sso.scim.expired")), 1)]),
								_: 1
							}))], 8, De)]),
							"connection-cell": K(({ row: e }) => [L(W(e.original.sso_connection ?? "—"), 1)]),
							"last_used-cell": K(({ row: e }) => [L(W(G(n)(e.original.last_used_at) ?? G(i)("guardian.sso.never")), 1)]),
							"expires-cell": K(({ row: e }) => [L(W(G(n)(e.original.expires_at) ?? G(i)("guardian.sso.never")), 1)]),
							"actions-cell": K(({ row: e }) => [e.original.revoked_at ? P("", !0) : (B(), N(r, {
								key: 0,
								size: "xs",
								color: "error",
								variant: "ghost",
								icon: "i-lucide-ban",
								"data-testid": `guardian-scim-revoke-${e.original.id}`,
								onClick: (t) => pe(e.original)
							}, {
								default: K(() => [L(W(G(i)("guardian.sso.scim.revoke")), 1)]),
								_: 1
							}, 8, ["data-testid", "onClick"]))]),
							empty: K(() => [R(u, {
								compact: "",
								title: G(i)("guardian.sso.scim.empty"),
								"data-testid": "guardian-scim-empty"
							}, null, 8, ["title"])]),
							_: 1
						}, 8, ["data", "columns"]),
						R(f, {
							current: G(j).current,
							last: G(j).last,
							testid: "guardian-scim-tokens",
							onGo: Q
						}, null, 8, ["current", "last"]),
						R(s, null, {
							header: K(() => [I("h3", Oe, W(G(i)("guardian.sso.scim.new")), 1)]),
							default: K(() => [I("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "guardian-scim-form",
								onSubmit: ie(de, ["prevent"])
							}, [
								R(y, {
									label: G(i)("guardian.sso.fields.name"),
									error: G(o).error("name"),
									required: ""
								}, {
									default: K(() => [R(v, {
										modelValue: G(z).name,
										"onUpdate:modelValue": t[0] ||= (e) => G(z).name = e,
										class: "w-56",
										"data-testid": "guardian-scim-name"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								R(y, {
									label: G(i)("guardian.sso.scim.expires_in_days"),
									error: G(o).error("expires_in_days")
								}, {
									default: K(() => [R(v, {
										modelValue: G(z).expires_in_days,
										"onUpdate:modelValue": t[1] ||= (e) => G(z).expires_in_days = e,
										type: "number",
										min: "1",
										max: "3650",
										class: "w-32",
										"data-testid": "guardian-scim-expires"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								R(y, {
									label: G(i)("guardian.sso.scim.link_connection"),
									error: G(o).error("sso_connection")
								}, {
									default: K(() => [R(b, {
										"model-value": G(h)(G(z).sso_connection),
										items: G(A),
										class: "w-56",
										"data-testid": "guardian-scim-connection",
										"onUpdate:modelValue": t[2] ||= (e) => G(z).sso_connection = G(m)(e)
									}, null, 8, ["model-value", "items"])]),
									_: 1
								}, 8, ["label", "error"]),
								R(r, {
									type: "submit",
									icon: "i-lucide-plus",
									loading: G(o).busy.value,
									"data-testid": "guardian-scim-submit"
								}, {
									default: K(() => [L(W(G(i)("guardian.sso.scim.create")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32), I("div", ke, [Object.keys(G(o).errors.value).length === 0 ? (B(), N(d, {
								key: 0,
								action: G(o),
								testid: "guardian-scim-issue"
							}, null, 8, ["action"])) : P("", !0)])]),
							_: 1
						})
					])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])
			]);
		};
	}
}), je = {
	key: 0,
	"data-testid": "hooks-guardian-scim"
}, Me = /* @__PURE__ */ z({
	__name: "GuardianScimTab",
	props: {
		data: {},
		record: {}
	},
	setup(e) {
		let t = e, n = r(), i = M(() => $(t.record)), a = M(() => typeof t.data.scim_path == "string" ? t.data.scim_path : null);
		return (e, t) => G(i) && G(n) ? (B(), F("div", je, [(B(), N(Ae, {
			key: G(i),
			organization: G(i),
			"scim-path": G(a)
		}, null, 8, ["organization", "scim-path"]))])) : P("", !0);
	}
}), Ne = { class: "font-semibold" }, Pe = { class: "divide-y divide-default text-sm" }, Fe = ["data-testid"], Ie = { class: "text-muted" }, Le = /* @__PURE__ */ z({
	__name: "GuardianSsoIdentities",
	props: {
		user: {},
		client: { default: void 0 }
	},
	setup(e) {
		let r = e, { t: i } = Z(), o = s(), { client: c, data: l, failed: u, status: f, reload: p } = t(async (e) => a(await e.GET("/users/{user}/sso-identities", { params: {
			path: { user: r.user },
			query: { per_page: 50 }
		} })), () => r.user, r.client === void 0 ? void 0 : r.client), m = M(() => l.value?.data ?? []);
		async function h(e) {
			let t = e.email ?? e.subject;
			c && window.confirm(i("guardian.sso.identities.confirm_unlink", {
				name: t,
				connection: e.connection
			})) && await o.run(async () => a(await c.DELETE("/sso-identities/{identity}", { params: { path: { identity: e.id } } }))) && await p();
		}
		return (e, t) => {
			let r = C, a = q, s = J;
			return G(u) && G(f) === 403 ? (B(), N(r, {
				key: 0,
				compact: ""
			})) : G(m).length > 0 ? (B(), N(s, {
				key: 1,
				"data-testid": "guardian-sso-identities"
			}, {
				header: K(() => [I("h3", Ne, W(G(i)("guardian.sso.identities.title")), 1)]),
				default: K(() => [I("ul", Pe, [(B(!0), F(j, null, U(G(m), (e) => (B(), F("li", {
					key: e.id,
					class: "flex items-center justify-between gap-2 py-2",
					"data-testid": `guardian-sso-identity-${e.id}`
				}, [I("span", null, [L(W(e.connection) + " — " + W(e.email ?? e.subject) + " ", 1), I("span", Ie, W(G(i)("guardian.sso.identities.last_login")) + ": " + W(G(n)(e.last_login_at) ?? G(i)("guardian.sso.never")), 1)]), R(a, {
					size: "xs",
					variant: "ghost",
					color: "error",
					icon: "i-lucide-unlink",
					"data-testid": `guardian-sso-identity-unlink-${e.id}`,
					onClick: (t) => h(e)
				}, {
					default: K(() => [L(W(G(i)("guardian.sso.identities.unlink")), 1)]),
					_: 1
				}, 8, ["data-testid", "onClick"])], 8, Fe))), 128))]), R(d, {
					action: G(o),
					testid: "guardian-sso-identity-unlink"
				}, null, 8, ["action"])]),
				_: 1
			})) : P("", !0);
		};
	}
}), Re = {
	key: 0,
	"data-testid": "hooks-guardian-sso-identities"
}, ze = /* @__PURE__ */ z({
	__name: "GuardianSsoIdentitiesSection",
	props: {
		data: {},
		record: {}
	},
	setup(e) {
		let t = e, n = r(), i = M(() => $(t.record));
		return (e, t) => G(i) && G(n) ? (B(), F("div", Re, [(B(), N(Le, {
			key: G(i),
			user: G(i)
		}, null, 8, ["user"]))])) : P("", !0);
	}
}), Be = {
	class: "space-y-4",
	"data-testid": "guardian-sso-connections"
}, Ve = { class: "space-y-4" }, He = ["data-testid"], Ue = { class: "font-medium" }, We = { class: "flex flex-wrap items-end gap-3" }, Ge = /* @__PURE__ */ z({
	__name: "GuardianSsoConnections",
	props: {
		organization: {},
		client: { default: void 0 }
	},
	setup(n) {
		let r = n, { t: i } = Z(), o = s(), c = H(1), u = M(() => ({
			per_page: 25,
			...c.value > 1 ? { page: c.value } : {}
		})), { client: m, data: h, pending: g, failed: b, status: S } = t(async (e) => a(await e.GET("/organizations/{organization}/sso-connections", { params: {
			path: { organization: r.organization },
			query: u.value
		} })), () => [r.organization, u.value], r.client === void 0 ? void 0 : r.client), C = M(() => h.value?.data ?? []), w = M(() => l(h.value)), T = M(() => [
			{
				id: "name",
				header: i("guardian.sso.fields.name")
			},
			{
				id: "protocol",
				header: i("guardian.sso.fields.protocol")
			},
			{
				id: "identities",
				header: i("guardian.sso.identities.title"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			}
		]), E = M(() => y.map((e) => ({
			label: i(`guardian.sso.protocols.${e}`),
			value: e
		}))), O = V({
			name: "",
			protocol: "oidc"
		}), k = V({});
		async function A() {
			if (!m) return;
			let t = { connection: null };
			await o.run(async () => {
				t.connection = a(await m.POST("/organizations/{organization}/sso-connections", {
					params: { path: { organization: r.organization } },
					body: e({
						name: O.name.trim(),
						protocol: O.protocol,
						..._(O.protocol, k)
					})
				})).data;
			}) && t.connection && await D(v(t.connection.slug));
		}
		function te(e) {
			c.value = e;
		}
		return (e, t) => {
			let n = Y, r = oe, a = ee, s = X, c = ce, l = se, u = le, m = q, _ = J;
			return B(), F("div", Be, [R(p, {
				ready: !G(g) && G(h) !== null,
				failed: G(b),
				status: G(S),
				compact: ""
			}, {
				default: K(() => [I("div", Ve, [
					R(s, {
						data: G(C),
						columns: G(T)
					}, {
						"name-cell": K(({ row: e }) => [I("div", {
							class: "flex flex-wrap items-center gap-2",
							"data-testid": `guardian-sso-connection-${e.original.slug}`
						}, [
							R(n, {
								to: G(v)(e.original.slug),
								class: "font-medium hover:underline",
								"data-testid": "guardian-sso-connection-link"
							}, {
								default: K(() => [L(W(e.original.name), 1)]),
								_: 2
							}, 1032, ["to"]),
							e.original.enforced ? (B(), N(r, {
								key: 0,
								color: "warning",
								variant: "subtle"
							}, {
								default: K(() => [L(W(G(i)("guardian.sso.enforced_badge")), 1)]),
								_: 1
							})) : P("", !0),
							e.original.enabled ? P("", !0) : (B(), N(r, {
								key: 1,
								color: "neutral",
								variant: "subtle"
							}, {
								default: K(() => [L(W(G(i)("guardian.sso.disabled_badge")), 1)]),
								_: 1
							}))
						], 8, He)]),
						"protocol-cell": K(({ row: e }) => [L(W(G(i)(`guardian.sso.protocols.${e.original.protocol}`, e.original.protocol)), 1)]),
						"identities-cell": K(({ row: e }) => [L(W(e.original.identities_count ?? "—"), 1)]),
						empty: K(() => [R(a, {
							compact: "",
							title: G(i)("guardian.sso.connections.empty"),
							"data-testid": "guardian-sso-connections-empty"
						}, null, 8, ["title"])]),
						_: 1
					}, 8, ["data", "columns"]),
					R(f, {
						current: G(w).current,
						last: G(w).last,
						testid: "guardian-sso-connections",
						onGo: te
					}, null, 8, ["current", "last"]),
					R(_, null, {
						header: K(() => [I("h3", Ue, W(G(i)("guardian.sso.connections.new")), 1)]),
						default: K(() => [I("form", {
							class: "space-y-3",
							"data-testid": "guardian-sso-new-form",
							onSubmit: ie(A, ["prevent"])
						}, [
							I("div", We, [R(l, {
								label: G(i)("guardian.sso.fields.name"),
								error: G(o).error("name"),
								required: ""
							}, {
								default: K(() => [R(c, {
									modelValue: G(O).name,
									"onUpdate:modelValue": t[0] ||= (e) => G(O).name = e,
									class: "w-56",
									"data-testid": "guardian-sso-new-name"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]), R(l, {
								label: G(i)("guardian.sso.fields.protocol"),
								error: G(o).error("protocol"),
								required: ""
							}, {
								default: K(() => [R(u, {
									modelValue: G(O).protocol,
									"onUpdate:modelValue": t[1] ||= (e) => G(O).protocol = e,
									items: G(E),
									class: "w-56",
									"data-testid": "guardian-sso-new-protocol"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"])]),
							R(x, {
								protocol: G(O).protocol,
								values: G(k),
								action: G(o),
								testid: "guardian-sso-new"
							}, null, 8, [
								"protocol",
								"values",
								"action"
							]),
							Object.keys(G(o).errors.value).length === 0 ? (B(), N(d, {
								key: 0,
								action: G(o),
								testid: "guardian-sso-new"
							}, null, 8, ["action"])) : P("", !0),
							R(m, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: G(o).busy.value,
								"data-testid": "guardian-sso-new-submit"
							}, {
								default: K(() => [L(W(G(i)("guardian.sso.connections.create")), 1)]),
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
			])]);
		};
	}
}), Ke = {
	key: 0,
	"data-testid": "hooks-guardian-sso"
}, qe = /* @__PURE__ */ z({
	__name: "GuardianSsoTab",
	props: {
		data: {},
		record: {}
	},
	setup(e) {
		let t = e, n = r(), i = M(() => $(t.record));
		return (e, t) => G(i) && G(n) ? (B(), F("div", Ke, [(B(), N(Ge, {
			key: G(i),
			organization: G(i)
		}, null, 8, ["organization"]))])) : P("", !0);
	}
}), Je = {
	key: 0,
	"data-testid": "hooks-guardian-user-security"
}, Ye = /* @__PURE__ */ z({
	__name: "GuardianUserSecurityTab",
	props: {
		data: {},
		record: {}
	},
	setup(e) {
		let t = e, n = r(), i = M(() => $(t.record));
		return (e, t) => G(i) && G(n) ? (B(), F("div", Je, [(B(), N(S, {
			key: G(i),
			user: G(i)
		}, null, 8, ["user"]))])) : P("", !0);
	}
}), Xe = { guardian: { access: {
	filter: "Filter",
	clear: "Clear",
	save: "Save",
	saved: "Saved.",
	pager: {
		previous: "Previous",
		next: "Next",
		page: "Page {current} of {last}"
	},
	scopes: {
		global: "Global",
		any: "Any scope"
	},
	roles: {
		title: "Roles",
		role: "Role",
		new: "New role",
		create: "Create",
		delete: "Delete role",
		empty: "No roles found.",
		not_found: "This role was not found.",
		super: "Super",
		built_in: "Built-in",
		everything: "Everything",
		no_permissions: "No permissions to choose from.",
		slug_hint: "Leave blank to make one from the name.",
		super_hint: "This role passes every permission check; it has no permission list.",
		confirm_delete: "Delete the role \"{name}\"? Everyone assigned it loses it.",
		fields: {
			name: "Name",
			slug: "Slug",
			scope: "Scope",
			description: "Description",
			permissions: "Permissions"
		}
	},
	permissions: {
		title: "Permissions",
		new: "New permission",
		create: "Create",
		delete: "Delete",
		empty: "No permissions found.",
		built_in: "Built-in",
		search: "Search",
		name_hint: "Lower-case, dot-separated, e.g. invoices.edit.",
		confirm_delete: "Delete the permission \"{name}\"? Roles that grant it lose it.",
		fields: {
			name: "Name",
			description: "Description"
		}
	},
	assignments: {
		title: "Role assignments",
		empty: "No role assignments.",
		assign: "Assign",
		revoke: "Revoke",
		pick_role: "Choose a role",
		any_scope: "Any scope",
		confirm_revoke: "Revoke the role \"{role}\" from user {user}?",
		fields: {
			user: "User",
			role: "Role",
			scope: "Scope",
			scope_type: "Scope type",
			scope_id: "Scope id",
			assigned_at: "Assigned"
		}
	},
	user: {
		title: "User {user}",
		assignments: "Role assignments",
		abilities: "Abilities",
		show_abilities: "Show",
		super_admin: "Super admin: every ability.",
		no_abilities: "No abilities here.",
		sessions: "Sessions",
		no_sessions: "No sessions.",
		revoke_sessions: "Sign out everywhere",
		confirm_revoke_sessions: "Sign {user} out of every session?",
		sessions_revoked: "Signed out {count} sessions.",
		tokens: "API tokens",
		no_tokens: "No API tokens.",
		never_used: "never used",
		revoke_tokens: "Revoke all tokens",
		confirm_revoke_tokens: "Revoke every API token of {user}?",
		events: "Recent events",
		no_events: "No security events.",
		two_factor: "Two-factor authentication",
		reset_two_factor: "Reset two-factor",
		reset_two_factor_hint: "Removes every two-factor method, passkey and recovery code, for a user who lost theirs.",
		confirm_reset_two_factor: "Reset two-factor authentication for {user}? They will have to set it up again.",
		two_factor_reset: "Two-factor authentication was reset."
	}
} } }, Ze = { guardian: { admin: {
	filter: "Filter",
	clear: "Clear",
	save: "Save",
	delete: "Delete",
	never: "Never",
	yes: "Yes",
	no: "No",
	events: {
		title: "Security events",
		empty: "No security events found.",
		any_type: "Any type",
		from: "From",
		to: "To",
		older: "Older",
		newer: "Newer",
		fields: {
			type: "Type",
			user: "User",
			ip: "IP address",
			occurred_at: "When"
		}
	},
	event: {
		title: "Security event",
		not_found: "This security event was not found.",
		user_agent: "User agent",
		identifier_hash: "Identifier hash",
		context: "Context",
		no_context: "This event has no context."
	},
	lockouts: {
		title: "Lockouts",
		clear: "Clear a lockout",
		hint: "Lets an identifier or address that failed too many sign-ins try again now.",
		identifier: "Identifier or IP address",
		submit: "Clear lockout",
		confirm: "Clear the lockout of {identifier}?",
		cleared: "The lockout of {identifier} was cleared.",
		recent: "Recent lockouts"
	},
	ip_rules: {
		title: "IP rules",
		new: "New IP rule",
		create: "Add rule",
		empty: "No IP rules.",
		cidr_hint: "An address or a CIDR range, such as 203.0.113.0/24.",
		confirm_delete: "Delete the IP rule for {cidr}?",
		confirm_allow_delete: "Guardian runs in allowlist mode: once the allow rule for {cidr} is gone, requests from it that match no other allow rule are refused, which may lock you out. Delete it anyway?",
		allowlist_warning: "Allowlist mode: a request matching no allow rule is refused.",
		mode: {
			allowlist: "Mode: allowlist. Only addresses an allow rule matches get in.",
			denylist: "Mode: denylist. Addresses a deny rule matches are refused."
		},
		actions: {
			allow: "Allow",
			deny: "Deny"
		},
		fields: {
			cidr: "CIDR",
			action: "Action",
			description: "Description",
			expires_at: "Expires"
		}
	},
	password_policy: {
		title: "Password policy",
		read_only: "Set in Guardian's configuration; read-only here.",
		length: "Length",
		length_value: "{min} to {max} characters",
		rules: {
			letters: "Needs a letter",
			mixed_case: "Needs upper and lower case",
			numbers: "Needs a number",
			symbols: "Needs a symbol"
		},
		uncompromised: "Breach check",
		uncompromised_on: "Refused when found in breaches more than {threshold} times",
		history: "History",
		history_value: "Cannot reuse the last {count} passwords",
		max_age: "Maximum age",
		max_age_value: "{days} days"
	},
	policies: {
		title: "Two-factor policies",
		new: "New two-factor policy",
		create: "Add policy",
		empty: "No two-factor policies.",
		everyone: "Everyone",
		any_role: "Any role",
		methods_hint: "Leave every method unticked to allow them all.",
		confirm_delete: "Delete the two-factor policy for {scope}?",
		methods: {
			totp: "Authenticator app",
			email: "Email",
			sms: "SMS",
			passkey: "Passkey"
		},
		fields: {
			scope: "Scope",
			scope_type: "Scope type",
			scope_id: "Scope ID",
			role: "Role",
			required: "Required",
			methods: "Methods",
			grace_days: "Grace days"
		}
	}
} } }, Qe = { guardian: { security: {
	title: "My security",
	two_factor: {
		title: "Two-factor authentication",
		on: "On",
		off: "Off",
		required: "Required for your account.",
		deadline: "Set it up by {deadline}."
	},
	sessions: {
		title: "Sessions",
		current: "This session",
		unknown: "Unknown browser",
		revoke: "Sign out",
		others: "Sign out everywhere else",
		also_tokens: "Also revoke my API tokens",
		confirm_revoke: "Sign this session out?",
		confirm_others: "Sign out every other session?",
		confirm_others_tokens: "Sign out every other session and revoke all your API tokens?",
		revoked: "Signed out {count} sessions and revoked {tokens} API tokens."
	},
	devices: {
		title: "Devices",
		unnamed: "Unknown device",
		forget: "Forget",
		none: "No devices yet.",
		confirm_forget: "Forget this device? Signing in from it will count as a new device."
	},
	tokens: {
		title: "API tokens",
		not_enabled: "API tokens are not enabled on this site.",
		none: "No API tokens.",
		name: "Name",
		abilities: "Abilities",
		every_ability: "Everything (*)",
		expires_at: "Expires on",
		expiry_hint: "No later than {date}.",
		expiry_too_late: "Tokens may last at most {days} days.",
		issue: "Issue token",
		plain_once: "Copy this token now. It will not be shown again.",
		copy: "Copy",
		copied: "Copied",
		done: "Done",
		expires: "expires {date}",
		no_expiry: "never expires",
		revoke: "Revoke",
		confirm_revoke: "Revoke the token \"{name}\"? Anything using it stops working."
	},
	activity: {
		title: "Recent activity",
		none: "No security events yet.",
		older: "Older",
		newer: "Newer"
	}
} } }, $e = { guardian: { sso: {
	save: "Save",
	saved: "Saved.",
	copy: "Copy",
	copied: "Copied",
	never: "Never",
	enforced_badge: "Enforced",
	disabled_badge: "Disabled",
	tenant_hint: "Your Entra tenant ID or verified domain. Multi-tenant values (common, organizations) are not allowed.",
	secret_hint: "Leave blank to keep the current value.",
	protocols: {
		oidc: "OpenID Connect",
		azure: "Microsoft Entra ID",
		saml: "SAML 2.0"
	},
	fields: {
		name: "Name",
		protocol: "Protocol",
		issuer: "Issuer",
		tenant: "Tenant",
		client_id: "Client ID",
		client_secret: "Client secret",
		metadata_url: "Metadata URL",
		entity_id: "Entity ID",
		sso_url: "SSO URL",
		certificate: "Certificate",
		jit: "Create accounts on first sign-in",
		enforced: "Require SSO for this organization's domains",
		enabled: "Enabled"
	},
	connections: {
		empty: "No SSO connections yet.",
		new: "New SSO connection",
		create: "Create"
	},
	connection: {
		title: "SSO connection",
		not_found: "This SSO connection was not found.",
		back: "Back to the organization",
		settings: "Settings",
		callback_url: "Callback / ACS URL",
		metadata_url: "Service provider metadata",
		test_sign_in: "Test sign-in",
		delete: "Delete connection",
		confirm_delete: "Delete the SSO connection {name}? Its members can no longer sign in through it."
	},
	scim: {
		base_url: "SCIM base URL",
		empty: "No SCIM tokens yet.",
		new: "New SCIM token",
		create: "Create",
		created: "Copy this token now. It will not be shown again.",
		connection: "SSO connection",
		link_connection: "Link to SSO connection",
		no_connection: "None",
		expires_in_days: "Expires in (days)",
		expires: "Expires",
		last_used: "Last used",
		revoked: "Revoked",
		expired: "Expired",
		revoke: "Revoke",
		confirm_revoke: "Revoke the SCIM token {name}? The identity provider using it stops syncing."
	},
	identities: {
		title: "SSO identities",
		last_login: "Last sign-in",
		unlink: "Unlink",
		confirm_unlink: "Unlink the SSO identity {name} from {connection}?"
	}
} } }, et = { guardian: { two_factor: {
	confirm: "Confirm",
	cancel: "Cancel",
	never_used: "never used",
	throttled: "Too many attempts.",
	retry_after: "Try again in {seconds} seconds.",
	types: {
		totp: "Authenticator app",
		email: "Email code",
		sms: "SMS code",
		passkey: "Passkey"
	},
	fields: {
		label: "Label",
		code: "Code",
		recovery_code: "Recovery code",
		type: "Send codes by",
		email: "Email address",
		phone: "Phone number (+15551234567)"
	},
	deadline: {
		notice: "Set up two-factor authentication by {deadline}.",
		link: "Set it up"
	},
	totp: {
		title: "Authenticator app",
		begin: "Set up an authenticator app",
		scan: "Scan this code with your authenticator app, then enter the code it shows.",
		qr_label: "QR code for your authenticator app",
		manual: "Or enter this key by hand:"
	},
	one_time: {
		title: "Email or SMS codes",
		send: "Send code",
		resend: "Send again",
		sent: "We sent you a code.",
		unavailable: "{type} is not available on this site."
	},
	passkeys: {
		title: "Passkeys",
		add: "Add a passkey",
		none: "No passkeys yet.",
		unnamed: "Passkey",
		unavailable: "Passkeys are not available here."
	},
	methods: {
		title: "Your methods",
		none: "No two-factor methods yet."
	},
	recovery: {
		title: "Recovery codes",
		once: "Save these codes somewhere safe. Each works once, and they will not be shown again.",
		copy: "Copy",
		copied: "Copied",
		download: "Download",
		done: "I saved them",
		remaining: "{count} recovery codes left.",
		regenerate: "Make new recovery codes",
		confirm_regenerate: "Make new recovery codes? The old ones stop working."
	},
	remove: {
		button: "Remove",
		proof: "Remove {name}: a code from another method, or a recovery code",
		proof_hint: "Proves it is you before a method goes.",
		confirm: "Remove",
		required: "Two-factor authentication is required, so your last method cannot be removed."
	},
	challenge: {
		title: "Two-factor authentication",
		intro: "Confirm it is you to continue.",
		submit: "Continue",
		use_passkey: "Use a passkey",
		load_failed: "Your two-factor methods could not be loaded.",
		try_later: "Wait a minute, then try again.",
		no_methods: "You have no two-factor method to use here.",
		methods: {
			totp: "Authenticator app",
			email: "Email code",
			sms: "SMS code",
			passkey: "Passkey",
			recovery: "Recovery code"
		}
	},
	setup: {
		title: "Set up two-factor authentication",
		intro: "This account needs a second step to sign in.",
		allowed: "Allowed methods: {methods}.",
		deadline: "Set it up by {deadline}.",
		load_failed: "Two-factor setup could not be loaded."
	}
} } }, tt = { guardian: { state: {
	not_found: "This record was not found.",
	load_failed: "Guardian could not be loaded."
} } }, nt = [
	{
		path: "/guardian/ip-rules",
		name: "guardian-ip-rules",
		component: () => import("./chunks/ip-rules-DC1O90Ex.js")
	},
	{
		path: "/guardian/lockouts",
		name: "guardian-lockouts",
		component: () => import("./chunks/lockouts-DtZlySb7.js")
	},
	{
		path: "/guardian/password-policy",
		name: "guardian-password-policy",
		component: () => import("./chunks/password-policy-D0nTk8g7.js")
	},
	{
		path: "/guardian/permissions",
		name: "guardian-permissions",
		component: () => import("./chunks/permissions-JoLfOecL.js")
	},
	{
		path: "/guardian/role-assignments",
		name: "guardian-role-assignments",
		component: () => import("./chunks/role-assignments-C5SHM34W.js")
	},
	{
		path: "/guardian/roles/:role",
		name: "guardian-roles-role",
		component: () => import("./chunks/_role_-u_ehN5Cx.js")
	},
	{
		path: "/guardian/roles",
		name: "guardian-roles",
		component: () => import("./chunks/roles-CyPBxncs.js")
	},
	{
		path: "/guardian/security-events/:event",
		name: "guardian-security-events-event",
		component: () => import("./chunks/_event_-VLs4n3t4.js")
	},
	{
		path: "/guardian/security-events",
		name: "guardian-security-events",
		component: () => import("./chunks/security-events-Byr3YoLC.js")
	},
	{
		path: "/guardian/security",
		name: "guardian-security",
		component: () => import("./chunks/security-C663bVJ-.js")
	},
	{
		path: "/guardian/sso-connections/:connection",
		name: "guardian-sso-connections-connection",
		component: () => import("./chunks/_connection_-CekwaDur.js")
	},
	{
		path: "/guardian/two-factor-policies",
		name: "guardian-two-factor-policies",
		component: () => import("./chunks/two-factor-policies-DtkP5x8w.js")
	},
	{
		path: "/guardian/two-factor/challenge",
		name: "guardian-two-factor-challenge",
		component: () => import("./chunks/challenge-STBF29qW.js"),
		meta: { atrium: { public: !0 } }
	},
	{
		path: "/guardian/two-factor/setup",
		name: "guardian-two-factor-setup",
		component: () => import("./chunks/setup-cTGlwhX1.js"),
		meta: { atrium: { public: !0 } }
	},
	{
		path: "/guardian/users/:user",
		name: "guardian-users-user",
		component: () => import("./chunks/_user_-BL0CQGTd.js")
	}
], rt = {
	"guardian.security": "guardian-security",
	"guardian.two-factor-challenge": "guardian-two-factor-challenge",
	"guardian.two-factor-setup": "guardian-two-factor-setup",
	"guardian.roles": "guardian-roles",
	"guardian.role": "guardian-roles-role",
	"guardian.permissions": "guardian-permissions",
	"guardian.role-assignments": "guardian-role-assignments",
	"guardian.user": "guardian-users-user",
	"guardian.security-events": "guardian-security-events",
	"guardian.security-event": "guardian-security-events-event",
	"guardian.lockouts": "guardian-lockouts",
	"guardian.ip-rules": "guardian-ip-rules",
	"guardian.two-factor-policies": "guardian-two-factor-policies",
	"guardian.password-policy": "guardian-password-policy",
	"guardian.sso-connection": "guardian-sso-connections-connection"
}, it = T({
	key: "guardian",
	routes: nt,
	screens: rt,
	extensions: {
		"guardian.access": be,
		"guardian.user-security": Ye,
		"guardian.sso": qe,
		"guardian.scim": Me,
		"guardian.sso-identities": ze
	},
	responseHandlers: { [ue]: ge(k()) },
	messages: { en: E(Xe, Ze, Qe, $e, et, tt) }
});
export { it as default, rt as screens };
