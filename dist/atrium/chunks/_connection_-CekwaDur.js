import { t as e } from "./GuardianErrors-DtyToZz8.js";
import { a as t, c as n, n as r, o as i, t as a } from "./SsoConnectionFields-BBomoNqB.js";
import { t as ee } from "./GuardianState-DytBr9ih.js";
import { c as o, d as s, o as c, u as l } from "./guardian-D9MeowUD.js";
import { AtriumIntegrationScreen as te, isExternalUrl as ne, navigateTo as u, useAtriumIntegration as d, useHead as re } from "@refactor-circus/atrium";
import { Fragment as ie, computed as f, createBlock as p, createCommentVNode as m, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as ae, openBlock as y, reactive as b, ref as x, toDisplayString as S, unref as C, watch as oe, withCtx as w, withModifiers as se } from "vue";
import { UButton as ce, UCard as le, UDashboardNavbar as T, UDashboardPanel as E, UFormField as D, UInput as O, USwitch as k } from "@refactor-circus/atrium/ui";
import { useI18n as A } from "vue-i18n";
import { useRoute as j } from "vue-router";
// src/pages/sso-connections/[connection].vue?vue&type=script&setup=true&lang.ts
var M = {
	key: 0,
	class: "space-y-4",
	"data-testid": "guardian-sso-connection"
}, N = { class: "font-semibold" }, P = { class: "grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[14rem_1fr]" }, F = { class: "text-muted" }, I = {
	class: "break-all font-mono text-xs",
	"data-testid": "guardian-sso-callback-url"
}, L = { class: "text-muted" }, R = {
	class: "break-all font-mono text-xs",
	"data-testid": "guardian-sso-metadata-url"
}, z = { class: "text-muted" }, B = { "data-testid": "guardian-sso-identities-count" }, V = { class: "font-semibold" }, H = { class: "flex flex-wrap gap-4 text-sm" }, U = { class: "flex items-center gap-3" }, ue = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "guardian-sso-saved"
}, de = { class: "space-y-2" }, W = /* @__PURE__ */ ae({
	__name: "[connection]",
	setup(ae) {
		let { t: W } = A(), fe = j(), G = l(), K = l(), pe = d("roster"), q = f(() => String(fe.params.connection)), { client: J, data: Y, pending: me, failed: he, status: ge, reload: _e } = s(async (e) => c(await e.GET("/sso-connections/{connection}", { params: { path: { connection: q.value } } })).data, q);
		re({ title: () => Y.value?.name ?? W("guardian.sso.connection.title") });
		let X = f(() => pe.active && Y.value?.organization ? i(Y.value.organization) : null), Z = b({
			name: "",
			enabled: !0,
			enforced: !1,
			jit: !0
		}), Q = b({}), $ = x(!1);
		oe(Y, (e) => {
			if (e) {
				Object.assign(Z, {
					name: e.name,
					enabled: e.enabled,
					enforced: e.enforced,
					jit: e.jit
				});
				for (let n of t(e.protocol)) Q[n] = r.includes(n) ? "" : e.settings?.[n] ?? "";
			}
		}, { immediate: !0 });
		async function ve() {
			let e = Y.value;
			J && e && ($.value = !1, $.value = await G.run(async () => c(await J.PATCH("/sso-connections/{connection}", {
				params: { path: { connection: e.slug } },
				body: o({
					name: Z.name.trim(),
					enabled: Z.enabled,
					enforced: Z.enforced,
					jit: Z.jit,
					...n(e.protocol, Q, !0)
				})
			}))), $.value && await _e());
		}
		async function ye() {
			let e = Y.value;
			if (!J || !e || !window.confirm(W("guardian.sso.connection.confirm_delete", { name: e.name }))) return;
			let t = X.value;
			await K.run(async () => c(await J.DELETE("/sso-connections/{connection}", { params: { path: { connection: e.slug } } }))) && await u(t ?? "/");
		}
		return (t, n) => {
			let r = ce, i = T, o = le, s = O, c = D, l = k, u = te, d = E;
			return y(), p(d, null, {
				header: w(() => [v(i, { title: C(Y)?.name ?? C(W)("guardian.sso.connection.title") }, {
					leading: w(() => [C(X) ? (y(), p(r, {
						key: 0,
						to: C(X),
						variant: "ghost",
						icon: "i-lucide-arrow-left",
						"aria-label": C(W)("guardian.sso.connection.back"),
						"data-testid": "guardian-sso-back"
					}, null, 8, ["to", "aria-label"])) : m("", !0)]),
					_: 1
				}, 8, ["title"])]),
				body: w(() => [v(u, {
					"screen-key": "guardian.sso-connection",
					record: C(q)
				}, {
					default: w(() => [v(ee, {
						ready: !C(me) && C(Y) !== null,
						failed: C(he),
						status: C(ge),
						"not-found": C(W)("guardian.sso.connection.not_found")
					}, {
						default: w(() => [C(Y) ? (y(), h("div", M, [
							v(o, null, {
								header: w(() => [g("h2", N, S(C(W)(`guardian.sso.protocols.${C(Y).protocol}`, C(Y).protocol)) + " · " + S(C(Y).organization), 1)]),
								default: w(() => [g("dl", P, [
									g("dt", F, S(C(W)("guardian.sso.connection.callback_url")), 1),
									g("dd", I, S(C(Y).callback_url ?? "—"), 1),
									C(Y).metadata_url ? (y(), h(ie, { key: 0 }, [g("dt", L, S(C(W)("guardian.sso.connection.metadata_url")), 1), g("dd", R, S(C(Y).metadata_url), 1)], 64)) : m("", !0),
									g("dt", z, S(C(W)("guardian.sso.identities.title")), 1),
									g("dd", B, S(C(Y).identities_count ?? "—"), 1)
								]), C(Y).sign_in_url && C(ne)(C(Y).sign_in_url) ? (y(), p(r, {
									key: 0,
									to: C(Y).sign_in_url,
									external: "",
									target: "_blank",
									rel: "noopener",
									class: "mt-3",
									variant: "ghost",
									icon: "i-lucide-external-link",
									"data-testid": "guardian-sso-test-sign-in"
								}, {
									default: w(() => [_(S(C(W)("guardian.sso.connection.test_sign_in")), 1)]),
									_: 1
								}, 8, ["to"])) : m("", !0)]),
								_: 1
							}),
							v(o, null, {
								header: w(() => [g("h2", V, S(C(W)("guardian.sso.connection.settings")), 1)]),
								default: w(() => [g("form", {
									class: "max-w-3xl space-y-4",
									"data-testid": "guardian-sso-form",
									onSubmit: se(ve, ["prevent"])
								}, [
									v(c, {
										label: C(W)("guardian.sso.fields.name"),
										error: C(G).error("name"),
										required: ""
									}, {
										default: w(() => [v(s, {
											modelValue: C(Z).name,
											"onUpdate:modelValue": n[0] ||= (e) => C(Z).name = e,
											class: "w-72",
											"data-testid": "guardian-sso-name"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									v(a, {
										protocol: C(Y).protocol,
										values: C(Q),
										action: C(G),
										editing: "",
										testid: "guardian-sso"
									}, null, 8, [
										"protocol",
										"values",
										"action"
									]),
									g("div", H, [
										v(l, {
											modelValue: C(Z).jit,
											"onUpdate:modelValue": n[1] ||= (e) => C(Z).jit = e,
											label: C(W)("guardian.sso.fields.jit"),
											"data-testid": "guardian-sso-jit"
										}, null, 8, ["modelValue", "label"]),
										v(l, {
											modelValue: C(Z).enforced,
											"onUpdate:modelValue": n[2] ||= (e) => C(Z).enforced = e,
											label: C(W)("guardian.sso.fields.enforced"),
											"data-testid": "guardian-sso-enforced"
										}, null, 8, ["modelValue", "label"]),
										v(l, {
											modelValue: C(Z).enabled,
											"onUpdate:modelValue": n[3] ||= (e) => C(Z).enabled = e,
											label: C(W)("guardian.sso.fields.enabled"),
											"data-testid": "guardian-sso-enabled"
										}, null, 8, ["modelValue", "label"])
									]),
									Object.keys(C(G).errors.value).length === 0 ? (y(), p(e, {
										key: 0,
										action: C(G),
										testid: "guardian-sso-save"
									}, null, 8, ["action"])) : m("", !0),
									g("div", U, [v(r, {
										type: "submit",
										icon: "i-lucide-check",
										loading: C(G).busy.value,
										"data-testid": "guardian-sso-save"
									}, {
										default: w(() => [_(S(C(W)("guardian.sso.save")), 1)]),
										_: 1
									}, 8, ["loading"]), C($) ? (y(), h("span", ue, S(C(W)("guardian.sso.saved")), 1)) : m("", !0)])
								], 32)]),
								_: 1
							}),
							g("div", de, [v(r, {
								color: "error",
								variant: "soft",
								icon: "i-lucide-trash",
								"data-testid": "guardian-sso-delete",
								onClick: ye
							}, {
								default: w(() => [_(S(C(W)("guardian.sso.connection.delete")), 1)]),
								_: 1
							}), v(e, {
								action: C(K),
								testid: "guardian-sso-delete"
							}, null, 8, ["action"])])
						])) : m("", !0)]),
						_: 1
					}, 8, [
						"ready",
						"failed",
						"status",
						"not-found"
					])]),
					_: 1
				}, 8, ["record"])]),
				_: 1
			});
		};
	}
});
export { W as default };
