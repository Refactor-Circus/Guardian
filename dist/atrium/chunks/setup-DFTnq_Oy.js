import { t as e } from "./client-DewBS3Os.js";
import { a as t, i as n, o as r, s as i } from "./guardian-BXKFb2X7.js";
import { n as a, t as o } from "./GuardianTwoFactorMethods-BK8U3knH.js";
import { AtriumErrorCard as s, AtriumNoAccess as c, atriumStatus as l, useAtriumShellStore as u, useHead as d } from "@refactor-circus/atrium";
import { computed as f, createBlock as p, createCommentVNode as m, createElementBlock as h, createElementVNode as g, createVNode as _, defineComponent as v, onBeforeUnmount as y, openBlock as b, ref as x, shallowRef as S, toDisplayString as C, unref as w, withCtx as T } from "vue";
import { UAlert as E, UCard as D, USkeleton as O } from "@refactor-circus/atrium/ui";
import { useI18n as k } from "vue-i18n";
import { useRoute as A, useRouter as j } from "vue-router";
// src/pages/two-factor/setup.vue?vue&type=script&setup=true&lang.ts
var M = { class: "min-w-0" }, N = { class: "text-lg font-semibold" }, P = { class: "mt-1 text-sm text-muted" }, F = {
	key: 4,
	class: "space-y-4"
}, I = {
	class: "text-sm",
	"data-testid": "guardian-setup-allowed"
}, L = /* @__PURE__ */ v({
	__name: "setup",
	setup(v) {
		let { t: L } = k(), R = A(), z = j(), B = u(), V = e();
		d({ title: () => L("guardian.two_factor.setup.title") });
		let H = x("loading"), U = S(null), W = x(null), G = f(() => i(U.value));
		async function K() {
			try {
				U.value = r(await V.GET("/two-factor")).data, H.value = "ready";
			} catch (e) {
				H.value = l(e) === 403 ? "forbidden" : "failed";
			}
		}
		async function q() {
			W.value = null, await B.load(!0), await z.replace(t(R.query.redirect));
		}
		async function J(e) {
			e && e.length > 0 ? W.value = e : await q();
		}
		return y(() => {
			W.value = null;
		}), K(), (e, t) => {
			let r = O, i = c, l = s, u = E, d = D;
			return b(), p(d, { "data-testid": "guardian-setup" }, {
				header: T(() => [g("div", M, [g("h1", N, C(w(L)("guardian.two_factor.setup.title")), 1), g("p", P, C(w(L)("guardian.two_factor.setup.intro")), 1)])]),
				default: T(() => [w(H) === "loading" ? (b(), p(r, {
					key: 0,
					class: "h-32",
					"data-testid": "guardian-setup-loading"
				})) : w(H) === "forbidden" ? (b(), p(i, { key: 1 })) : w(H) === "failed" || !w(U) ? (b(), p(l, {
					key: 2,
					message: w(L)("guardian.two_factor.setup.load_failed")
				}, null, 8, ["message"])) : w(W) ? (b(), p(a, {
					key: 3,
					codes: w(W),
					onDone: q
				}, null, 8, ["codes"])) : (b(), h("div", F, [
					g("p", I, C(w(L)("guardian.two_factor.setup.allowed", { methods: w(G).map((e) => w(L)(`guardian.two_factor.types.${e}`)).join(", ") })), 1),
					w(U).enrolment_deadline ? (b(), p(u, {
						key: 0,
						color: "warning",
						variant: "subtle",
						title: w(L)("guardian.two_factor.setup.deadline", { deadline: w(n)(w(U).enrolment_deadline) ?? w(U).enrolment_deadline }),
						"data-testid": "guardian-setup-deadline"
					}, null, 8, ["title"])) : m("", !0),
					_(o, {
						client: w(V),
						status: w(U),
						setup: "",
						onConfirmed: J
					}, null, 8, ["client", "status"])
				]))]),
				_: 1
			});
		};
	}
});
export { L as default };
