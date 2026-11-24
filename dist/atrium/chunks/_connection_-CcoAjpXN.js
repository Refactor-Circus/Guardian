import { t as e } from "./GuardianErrors-mhgGBfyk.js";
import { a as t, c as n, n as r, o as i, t as a } from "./SsoConnectionFields-BBomoNqB.js";
import { t as o } from "./GuardianState-DytBr9ih.js";
import { d as s, f as c, l, o as u } from "./guardian-BXKFb2X7.js";
import { AtriumDetails as d, AtriumIntegrationScreen as f, isExternalUrl as p, navigateTo as m, useAtriumIntegration as h, useHead as g } from "@refactor-circus/atrium";
import { computed as _, createBlock as v, createCommentVNode as y, createElementBlock as b, createElementVNode as x, createTextVNode as S, createVNode as C, defineComponent as ee, openBlock as w, reactive as T, ref as te, toDisplayString as E, unref as D, watch as ne, withCtx as O, withModifiers as re } from "vue";
import { UButton as ie, UCard as ae, UDashboardNavbar as oe, UDashboardPanel as k, UFormField as A, UInput as j, USwitch as M } from "@refactor-circus/atrium/ui";
import { useI18n as N } from "vue-i18n";
import { useRoute as P } from "vue-router";
// src/pages/sso-connections/[connection].vue?vue&type=script&setup=true&lang.ts
var F = {
	key: 0,
	class: "space-y-4",
	"data-testid": "guardian-sso-connection"
}, I = { class: "font-semibold" }, L = { class: "font-semibold" }, R = { class: "flex flex-wrap gap-4 text-sm" }, z = { class: "flex items-center gap-3" }, B = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "guardian-sso-saved"
}, V = { class: "space-y-2" }, H = /* @__PURE__ */ ee({
	__name: "[connection]",
	setup(ee) {
		let { t: H } = N(), U = P(), W = s(), G = s(), K = h("roster"), q = _(() => String(U.params.connection)), { client: J, data: Y, pending: se, failed: ce, status: le, reload: ue } = c(async (e) => u(await e.GET("/sso-connections/{connection}", { params: { path: { connection: q.value } } })).data, q);
		g({ title: () => Y.value?.name ?? H("guardian.sso.connection.title") });
		let de = _(() => {
			let e = Y.value;
			return e ? [
				{
					label: H("guardian.sso.connection.callback_url"),
					value: e.callback_url,
					mono: !0,
					testid: "guardian-sso-callback-url"
				},
				...e.metadata_url ? [{
					label: H("guardian.sso.connection.metadata_url"),
					value: e.metadata_url,
					mono: !0,
					testid: "guardian-sso-metadata-url"
				}] : [],
				{
					label: H("guardian.sso.identities.title"),
					value: e.identities_count,
					testid: "guardian-sso-identities-count"
				}
			] : [];
		}), X = _(() => K.active && Y.value?.organization ? i(Y.value.organization) : null), Z = T({
			name: "",
			enabled: !0,
			enforced: !1,
			jit: !0
		}), Q = T({}), $ = te(!1);
		ne(Y, (e) => {
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
		async function fe() {
			let e = Y.value;
			J && e && ($.value = !1, $.value = await W.run(async () => u(await J.PATCH("/sso-connections/{connection}", {
				params: { path: { connection: e.slug } },
				body: l({
					name: Z.name.trim(),
					enabled: Z.enabled,
					enforced: Z.enforced,
					jit: Z.jit,
					...n(e.protocol, Q, !0)
				})
			}))), $.value && await ue());
		}
		async function pe() {
			let e = Y.value;
			if (!J || !e || !window.confirm(H("guardian.sso.connection.confirm_delete", { name: e.name }))) return;
			let t = X.value;
			await G.run(async () => u(await J.DELETE("/sso-connections/{connection}", { params: { path: { connection: e.slug } } }))) && await m(t ?? "/");
		}
		return (t, n) => {
			let r = ie, i = oe, s = d, c = ae, l = j, u = A, m = M, h = f, g = k;
			return w(), v(g, null, {
				header: O(() => [C(i, { title: D(Y)?.name ?? D(H)("guardian.sso.connection.title") }, {
					leading: O(() => [D(X) ? (w(), v(r, {
						key: 0,
						to: D(X),
						variant: "ghost",
						icon: "i-lucide-arrow-left",
						"aria-label": D(H)("guardian.sso.connection.back"),
						"data-testid": "guardian-sso-back"
					}, null, 8, ["to", "aria-label"])) : y("", !0)]),
					_: 1
				}, 8, ["title"])]),
				body: O(() => [C(h, {
					"screen-key": "guardian.sso-connection",
					record: D(q)
				}, {
					default: O(() => [C(o, {
						ready: !D(se) && D(Y) !== null,
						failed: D(ce),
						status: D(le),
						"not-found": D(H)("guardian.sso.connection.not_found")
					}, {
						default: O(() => [D(Y) ? (w(), b("div", F, [
							C(c, null, {
								header: O(() => [x("h2", I, E(D(H)(`guardian.sso.protocols.${D(Y).protocol}`, D(Y).protocol)) + " · " + E(D(Y).organization), 1)]),
								default: O(() => [C(s, { items: D(de) }, null, 8, ["items"]), D(Y).sign_in_url && D(p)(D(Y).sign_in_url) ? (w(), v(r, {
									key: 0,
									to: D(Y).sign_in_url,
									external: "",
									target: "_blank",
									rel: "noopener",
									class: "mt-3",
									variant: "ghost",
									icon: "i-lucide-external-link",
									"data-testid": "guardian-sso-test-sign-in"
								}, {
									default: O(() => [S(E(D(H)("guardian.sso.connection.test_sign_in")), 1)]),
									_: 1
								}, 8, ["to"])) : y("", !0)]),
								_: 1
							}),
							C(c, null, {
								header: O(() => [x("h2", L, E(D(H)("guardian.sso.connection.settings")), 1)]),
								default: O(() => [x("form", {
									class: "max-w-3xl space-y-4",
									"data-testid": "guardian-sso-form",
									onSubmit: re(fe, ["prevent"])
								}, [
									C(u, {
										label: D(H)("guardian.sso.fields.name"),
										error: D(W).error("name"),
										required: ""
									}, {
										default: O(() => [C(l, {
											modelValue: D(Z).name,
											"onUpdate:modelValue": n[0] ||= (e) => D(Z).name = e,
											class: "w-72",
											"data-testid": "guardian-sso-name"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									C(a, {
										protocol: D(Y).protocol,
										values: D(Q),
										action: D(W),
										editing: "",
										testid: "guardian-sso"
									}, null, 8, [
										"protocol",
										"values",
										"action"
									]),
									x("div", R, [
										C(m, {
											modelValue: D(Z).jit,
											"onUpdate:modelValue": n[1] ||= (e) => D(Z).jit = e,
											label: D(H)("guardian.sso.fields.jit"),
											"data-testid": "guardian-sso-jit"
										}, null, 8, ["modelValue", "label"]),
										C(m, {
											modelValue: D(Z).enforced,
											"onUpdate:modelValue": n[2] ||= (e) => D(Z).enforced = e,
											label: D(H)("guardian.sso.fields.enforced"),
											"data-testid": "guardian-sso-enforced"
										}, null, 8, ["modelValue", "label"]),
										C(m, {
											modelValue: D(Z).enabled,
											"onUpdate:modelValue": n[3] ||= (e) => D(Z).enabled = e,
											label: D(H)("guardian.sso.fields.enabled"),
											"data-testid": "guardian-sso-enabled"
										}, null, 8, ["modelValue", "label"])
									]),
									Object.keys(D(W).errors.value).length === 0 ? (w(), v(e, {
										key: 0,
										action: D(W),
										testid: "guardian-sso-save"
									}, null, 8, ["action"])) : y("", !0),
									x("div", z, [C(r, {
										type: "submit",
										icon: "i-lucide-check",
										loading: D(W).busy.value,
										"data-testid": "guardian-sso-save"
									}, {
										default: O(() => [S(E(D(H)("guardian.sso.save")), 1)]),
										_: 1
									}, 8, ["loading"]), D($) ? (w(), b("span", B, E(D(H)("guardian.sso.saved")), 1)) : y("", !0)])
								], 32)]),
								_: 1
							}),
							x("div", V, [C(r, {
								color: "error",
								variant: "soft",
								icon: "i-lucide-trash",
								"data-testid": "guardian-sso-delete",
								onClick: pe
							}, {
								default: O(() => [S(E(D(H)("guardian.sso.connection.delete")), 1)]),
								_: 1
							}), C(e, {
								action: D(G),
								testid: "guardian-sso-delete"
							}, null, 8, ["action"])])
						])) : y("", !0)]),
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
export { H as default };
