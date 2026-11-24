import { t as e } from "./GuardianActionError-DKEMy_E4.js";
import { d as t, i as n, l as r, o as i, r as a, s as o } from "./guardian-BXKFb2X7.js";
import { r as s, t as c } from "./webauthn-DJJMk2td.js";
import { atriumStatus as l } from "@refactor-circus/atrium";
import { Fragment as u, computed as d, createBlock as f, createCommentVNode as p, createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as v, isRef as y, onBeforeUnmount as b, openBlock as x, ref as S, renderList as C, shallowRef as w, toDisplayString as T, unref as E, watch as D, withCtx as O, withModifiers as k } from "vue";
import { UAlert as A, UButton as j, UCard as M, UFormField as N, UInput as P, USelect as F } from "@refactor-circus/atrium/ui";
import { useI18n as I } from "vue-i18n";
// src/components/two-factor/GuardianFactorRemoval.vue?vue&type=script&setup=true&lang.ts
var L = ["data-testid"], R = /* @__PURE__ */ v({
	__name: "GuardianFactorRemoval",
	props: {
		client: {},
		kind: {},
		id: {},
		name: {}
	},
	emits: ["removed"],
	setup(n, { emit: a }) {
		let o = n, s = a, { t: c } = I(), l = t(), u = S(!1), p = S(""), h = d(() => l.code.value === "two_factor_required");
		function v(e) {
			u.value = e;
		}
		async function b() {
			let e = r({ code: p.value.trim() }), t = await l.run(async () => {
				i(o.kind === "method" ? await o.client.DELETE("/two-factor/methods/{method}", {
					params: { path: { method: o.id } },
					body: e
				}) : await o.client.DELETE("/two-factor/passkeys/{passkey}", {
					params: { path: { passkey: o.id } },
					body: e
				}));
			});
			p.value = "", t && (u.value = !1, s("removed"));
		}
		return (t, r) => {
			let i = j, a = P, o = N, s = A;
			return x(), m("div", { "data-testid": `guardian-remove-${n.id}` }, [E(u) ? (x(), m("form", {
				key: 1,
				class: "flex flex-wrap items-end gap-2",
				"data-testid": "guardian-remove-form",
				onSubmit: k(b, ["prevent"])
			}, [
				_(o, {
					label: E(c)("guardian.two_factor.remove.proof", { name: n.name }),
					hint: E(c)("guardian.two_factor.remove.proof_hint"),
					error: E(l).error("code"),
					required: ""
				}, {
					default: O(() => [_(a, {
						modelValue: E(p),
						"onUpdate:modelValue": r[1] ||= (e) => y(p) ? p.value = e : null,
						autocomplete: "one-time-code",
						"data-testid": "guardian-remove-code"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, [
					"label",
					"hint",
					"error"
				]),
				_(i, {
					type: "submit",
					color: "error",
					loading: E(l).busy.value,
					"data-testid": "guardian-remove-submit"
				}, {
					default: O(() => [g(T(E(c)("guardian.two_factor.remove.confirm")), 1)]),
					_: 1
				}, 8, ["loading"]),
				_(i, {
					color: "neutral",
					variant: "ghost",
					onClick: r[2] ||= (e) => v(!1)
				}, {
					default: O(() => [g(T(E(c)("guardian.two_factor.cancel")), 1)]),
					_: 1
				})
			], 32)) : (x(), f(i, {
				key: 0,
				size: "xs",
				color: "error",
				variant: "ghost",
				icon: "i-lucide-trash-2",
				"data-testid": "guardian-remove-open",
				onClick: r[0] ||= (e) => v(!0)
			}, {
				default: O(() => [g(T(E(c)("guardian.two_factor.remove.button")), 1)]),
				_: 1
			})), E(h) ? (x(), f(s, {
				key: 2,
				color: "warning",
				variant: "subtle",
				title: E(c)("guardian.two_factor.remove.required"),
				description: E(l).message.value ?? void 0,
				"data-testid": "guardian-remove-required"
			}, null, 8, ["title", "description"])) : (x(), f(e, {
				key: 3,
				action: E(l)
			}, null, 8, ["action"]))], 8, L);
		};
	}
}), z = {
	class: "space-y-3",
	"data-testid": "guardian-one-time"
}, B = { class: "font-medium" }, V = /* @__PURE__ */ v({
	__name: "GuardianOneTimeCodes",
	props: {
		client: {},
		types: {}
	},
	emits: ["confirmed"],
	setup(n, { emit: r }) {
		let a = n, o = r, { t: s } = I(), c = t(), l = t(), v = S([]), b = S(null), C = S(""), w = S(!1), M = S(""), L = S(null), R = d(() => a.types.filter((e) => !v.value.includes(e))), V = d(() => R.value.map((e) => ({
			label: s(`guardian.two_factor.types.${e}`),
			value: e
		})));
		D(R, (e) => {
			b.value !== null && e.includes(b.value) || (b.value = e[0] ?? null);
		}, { immediate: !0 });
		async function ee() {
			let e = b.value;
			e && (L.value = null, await c.run(async () => {
				i(await a.client.POST("/two-factor/one-time-codes", { body: {
					type: e,
					destination: C.value.trim()
				} }));
			}) ? w.value = !0 : c.code.value === "method_unavailable" && (v.value = [...v.value, e], L.value = s("guardian.two_factor.one_time.unavailable", { type: s(`guardian.two_factor.types.${e}`) }), w.value = !1));
		}
		async function te() {
			let e = b.value;
			if (!e) return;
			let t = null, n = await l.run(async () => {
				t = i(await a.client.POST("/two-factor/one-time-codes/confirm", { body: {
					type: e,
					code: M.value.trim()
				} })).data.recovery_codes ?? null;
			});
			M.value = "", n && (w.value = !1, C.value = "", o("confirmed", t));
		}
		return (t, n) => {
			let r = A, i = F, a = N, o = P, d = j;
			return x(), m("div", z, [
				h("h3", B, T(E(s)("guardian.two_factor.one_time.title")), 1),
				E(L) ? (x(), f(r, {
					key: 0,
					color: "neutral",
					variant: "subtle",
					title: E(L),
					"data-testid": "guardian-one-time-unavailable"
				}, null, 8, ["title"])) : p("", !0),
				E(R).length > 0 ? (x(), m(u, { key: 1 }, [
					h("form", {
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "guardian-one-time-send",
						onSubmit: k(ee, ["prevent"])
					}, [
						_(a, { label: E(s)("guardian.two_factor.fields.type") }, {
							default: O(() => [_(i, {
								"model-value": E(b) ?? void 0,
								items: E(V),
								class: "w-32",
								"data-testid": "guardian-one-time-type",
								"onUpdate:modelValue": n[0] ||= (e) => b.value = e
							}, null, 8, ["model-value", "items"])]),
							_: 1
						}, 8, ["label"]),
						_(a, {
							label: E(s)(E(b) === "sms" ? "guardian.two_factor.fields.phone" : "guardian.two_factor.fields.email"),
							error: E(c).error("destination"),
							required: ""
						}, {
							default: O(() => [_(o, {
								modelValue: E(C),
								"onUpdate:modelValue": n[1] ||= (e) => y(C) ? C.value = e : null,
								type: E(b) === "sms" ? "tel" : "email",
								"data-testid": "guardian-one-time-destination"
							}, null, 8, ["modelValue", "type"])]),
							_: 1
						}, 8, ["label", "error"]),
						_(d, {
							type: "submit",
							loading: E(c).busy.value,
							"data-testid": "guardian-one-time-submit-send"
						}, {
							default: O(() => [g(T(E(w) ? E(s)("guardian.two_factor.one_time.resend") : E(s)("guardian.two_factor.one_time.send")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32),
					E(c).code.value === "method_unavailable" ? p("", !0) : (x(), f(e, {
						key: 0,
						action: E(c)
					}, null, 8, ["action"])),
					E(w) ? (x(), m("form", {
						key: 1,
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "guardian-one-time-confirm",
						onSubmit: k(te, ["prevent"])
					}, [_(a, {
						label: E(s)("guardian.two_factor.fields.code"),
						hint: E(s)("guardian.two_factor.one_time.sent"),
						error: E(l).error("code"),
						required: ""
					}, {
						default: O(() => [_(o, {
							modelValue: E(M),
							"onUpdate:modelValue": n[2] ||= (e) => y(M) ? M.value = e : null,
							autocomplete: "one-time-code",
							inputmode: "numeric",
							"data-testid": "guardian-one-time-code"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, [
						"label",
						"hint",
						"error"
					]), _(d, {
						type: "submit",
						loading: E(l).busy.value,
						"data-testid": "guardian-one-time-submit"
					}, {
						default: O(() => [g(T(E(s)("guardian.two_factor.confirm")), 1)]),
						_: 1
					}, 8, ["loading"])], 32)) : p("", !0),
					_(e, { action: E(l) }, null, 8, ["action"])
				], 64)) : p("", !0)
			]);
		};
	}
}), ee = {
	class: "space-y-3",
	"data-testid": "guardian-passkeys"
}, te = { class: "font-medium" }, ne = {
	key: 0,
	class: "divide-y divide-default text-sm"
}, re = ["data-testid"], ie = { class: "text-muted" }, ae = {
	key: 0,
	class: "py-2 text-muted"
}, H = /* @__PURE__ */ v({
	__name: "GuardianPasskeys",
	props: {
		client: {},
		available: { type: Boolean },
		manage: {
			type: Boolean,
			default: !0
		}
	},
	emits: ["confirmed", "changed"],
	setup(a, { emit: o }) {
		let v = a, b = o, { t: D } = I(), M = t(), F = w([]), L = S(!1), z = S(""), B = s(), V = d(() => !v.available || L.value || !B);
		async function H() {
			if (v.manage && v.available && B) try {
				F.value = i(await v.client.GET("/two-factor/passkeys")).data;
			} catch (e) {
				L.value = l(e) === 501, F.value = [];
			}
		}
		async function oe() {
			let e = null, t = await M.run(async () => {
				let t = i(await v.client.POST("/two-factor/passkeys/options")).data, n = await c(t);
				e = i(await v.client.POST("/two-factor/passkeys", { body: r({
					credential: n,
					label: z.value.trim() || null
				}) })).data.recovery_codes ?? null;
			});
			M.status.value === 501 && (L.value = !0), t && (z.value = "", await H(), b("confirmed", e));
		}
		async function se() {
			await H(), b("changed");
		}
		return H(), (t, r) => {
			let i = A, o = P, s = N, c = j;
			return x(), m("div", ee, [h("h3", te, T(E(D)("guardian.two_factor.passkeys.title")), 1), E(V) ? (x(), f(i, {
				key: 0,
				color: "neutral",
				variant: "subtle",
				title: E(D)("guardian.two_factor.passkeys.unavailable"),
				"data-testid": "guardian-passkeys-unavailable"
			}, null, 8, ["title"])) : (x(), m(u, { key: 1 }, [
				a.manage ? (x(), m("ul", ne, [(x(!0), m(u, null, C(E(F), (e) => (x(), m("li", {
					key: e.id,
					class: "flex flex-wrap items-center justify-between gap-2 py-2",
					"data-testid": `guardian-passkey-${e.id}`
				}, [h("span", null, [g(T(e.label ?? E(D)("guardian.two_factor.passkeys.unnamed")) + " ", 1), h("span", ie, T(E(n)(e.last_used_at) ?? E(D)("guardian.two_factor.never_used")), 1)]), _(R, {
					client: a.client,
					kind: "passkey",
					id: e.id,
					name: e.label ?? E(D)("guardian.two_factor.passkeys.unnamed"),
					onRemoved: se
				}, null, 8, [
					"client",
					"id",
					"name"
				])], 8, re))), 128)), E(F).length === 0 ? (x(), m("li", ae, T(E(D)("guardian.two_factor.passkeys.none")), 1)) : p("", !0)])) : p("", !0),
				_(e, { action: E(M) }, null, 8, ["action"]),
				h("form", {
					class: "flex flex-wrap items-end gap-2",
					"data-testid": "guardian-passkey-register",
					onSubmit: k(oe, ["prevent"])
				}, [_(s, {
					label: E(D)("guardian.two_factor.fields.label"),
					error: E(M).error("label")
				}, {
					default: O(() => [_(o, {
						modelValue: E(z),
						"onUpdate:modelValue": r[0] ||= (e) => y(z) ? z.value = e : null,
						"data-testid": "guardian-passkey-label"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label", "error"]), _(c, {
					type: "submit",
					icon: "i-lucide-key-round",
					loading: E(M).busy.value,
					"data-testid": "guardian-passkey-submit"
				}, {
					default: O(() => [g(T(E(D)("guardian.two_factor.passkeys.add")), 1)]),
					_: 1
				}, 8, ["loading"])], 32)
			], 64))]);
		};
	}
}), oe = { class: "font-medium" }, se = { class: "mb-3 text-sm text-muted" }, ce = { class: "mb-3 grid grid-cols-2 gap-1 font-mono text-sm" }, le = { class: "flex flex-wrap gap-2" }, ue = /* @__PURE__ */ v({
	__name: "GuardianRecoveryCodes",
	props: { codes: {} },
	emits: ["done"],
	setup(e, { emit: t }) {
		let n = e, r = t, { t: i } = I(), a = S(!1), o = d(() => `${n.codes.join("\n")}\n`);
		async function s() {
			await navigator.clipboard?.writeText(o.value), a.value = !0;
		}
		function c() {
			let e = URL.createObjectURL(new Blob([o.value], { type: "text/plain" })), t = document.createElement("a");
			t.href = e, t.download = "recovery-codes.txt", t.click(), URL.revokeObjectURL(e);
		}
		return (t, n) => {
			let o = j, l = M;
			return x(), f(l, { "data-testid": "guardian-recovery-codes" }, {
				header: O(() => [h("h3", oe, T(E(i)("guardian.two_factor.recovery.title")), 1)]),
				default: O(() => [
					h("p", se, T(E(i)("guardian.two_factor.recovery.once")), 1),
					h("ul", ce, [(x(!0), m(u, null, C(e.codes, (e) => (x(), m("li", {
						key: e,
						"data-testid": "guardian-recovery-code"
					}, T(e), 1))), 128))]),
					h("div", le, [
						_(o, {
							icon: "i-lucide-copy",
							color: "neutral",
							variant: "soft",
							"data-testid": "guardian-recovery-copy",
							onClick: s
						}, {
							default: O(() => [g(T(E(a) ? E(i)("guardian.two_factor.recovery.copied") : E(i)("guardian.two_factor.recovery.copy")), 1)]),
							_: 1
						}),
						_(o, {
							icon: "i-lucide-download",
							color: "neutral",
							variant: "soft",
							"data-testid": "guardian-recovery-download",
							onClick: c
						}, {
							default: O(() => [g(T(E(i)("guardian.two_factor.recovery.download")), 1)]),
							_: 1
						}),
						_(o, {
							"data-testid": "guardian-recovery-done",
							onClick: n[0] ||= (e) => r("done")
						}, {
							default: O(() => [g(T(E(i)("guardian.two_factor.recovery.done")), 1)]),
							_: 1
						})
					])
				]),
				_: 1
			});
		};
	}
}), U = /* @__PURE__ */ ((e) => (e[e.Border = -1] = "Border", e[e.Data = 0] = "Data", e[e.Function = 1] = "Function", e[e.Position = 2] = "Position", e[e.Timing = 3] = "Timing", e[e.Alignment = 4] = "Alignment", e))(U || {}), de = [0, 1], fe = [1, 0], pe = [2, 3], me = [3, 2], he = {
	L: de,
	M: fe,
	Q: pe,
	H: me
}, ge = /^\d*$/, _e = /^[A-Z0-9 $%*+./:-]*$/, W = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:", G = 1, K = 40, ve = 3, ye = 3, q = 40, be = 10, xe = [
	[
		-1,
		7,
		10,
		15,
		20,
		26,
		18,
		20,
		24,
		30,
		18,
		20,
		24,
		26,
		30,
		22,
		24,
		28,
		30,
		28,
		28,
		28,
		28,
		30,
		30,
		26,
		28,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30
	],
	[
		-1,
		10,
		16,
		26,
		18,
		24,
		16,
		18,
		22,
		22,
		26,
		30,
		22,
		22,
		24,
		24,
		28,
		28,
		26,
		26,
		26,
		26,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28,
		28
	],
	[
		-1,
		13,
		22,
		18,
		26,
		18,
		24,
		18,
		22,
		20,
		24,
		28,
		26,
		24,
		20,
		30,
		24,
		28,
		28,
		26,
		30,
		28,
		30,
		30,
		30,
		30,
		28,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30
	],
	[
		-1,
		17,
		28,
		22,
		16,
		22,
		28,
		26,
		26,
		24,
		28,
		24,
		28,
		22,
		24,
		24,
		30,
		28,
		28,
		26,
		28,
		30,
		24,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30,
		30
	]
], Se = [
	[
		-1,
		1,
		1,
		1,
		1,
		1,
		2,
		2,
		2,
		2,
		4,
		4,
		4,
		4,
		4,
		6,
		6,
		6,
		6,
		7,
		8,
		8,
		9,
		9,
		10,
		12,
		12,
		12,
		13,
		14,
		15,
		16,
		17,
		18,
		19,
		19,
		20,
		21,
		22,
		24,
		25
	],
	[
		-1,
		1,
		1,
		1,
		2,
		2,
		4,
		4,
		4,
		5,
		5,
		5,
		8,
		9,
		9,
		10,
		10,
		11,
		13,
		14,
		16,
		17,
		17,
		18,
		20,
		21,
		23,
		25,
		26,
		28,
		29,
		31,
		33,
		35,
		37,
		38,
		40,
		43,
		45,
		47,
		49
	],
	[
		-1,
		1,
		1,
		2,
		2,
		4,
		4,
		6,
		6,
		8,
		8,
		8,
		10,
		12,
		16,
		12,
		17,
		16,
		18,
		21,
		20,
		23,
		23,
		25,
		27,
		29,
		34,
		34,
		35,
		38,
		40,
		43,
		45,
		48,
		51,
		53,
		56,
		59,
		62,
		65,
		68
	],
	[
		-1,
		1,
		1,
		2,
		4,
		4,
		4,
		5,
		6,
		8,
		8,
		11,
		11,
		16,
		16,
		18,
		16,
		19,
		21,
		25,
		25,
		25,
		34,
		30,
		32,
		35,
		37,
		40,
		42,
		45,
		48,
		51,
		54,
		57,
		60,
		63,
		66,
		70,
		74,
		77,
		81
	]
], Ce = class {
	constructor(e, t, n, r) {
		if (this.version = e, this.ecc = t, e < G || e > K) throw RangeError("Version value out of range");
		if (r < -1 || r > 7) throw RangeError("Mask value out of range");
		this.size = e * 4 + 17;
		let i = Array.from({ length: this.size }).fill(!1);
		for (let e = 0; e < this.size; e++) this.modules.push(i.slice()), this.types.push(i.map(() => 0));
		this.drawFunctionPatterns();
		let a = this.addEccAndInterleave(n);
		if (this.drawCodewords(a), r === -1) {
			let e = 1e9;
			for (let t = 0; t < 8; t++) {
				this.applyMask(t), this.drawFormatBits(t);
				let n = this.getPenaltyScore();
				n < e && (r = t, e = n), this.applyMask(t);
			}
		}
		this.mask = r, this.applyMask(r), this.drawFormatBits(r);
	}
	size;
	mask;
	modules = [];
	types = [];
	getModule(e, t) {
		return e >= 0 && e < this.size && t >= 0 && t < this.size && this.modules[t][e];
	}
	drawFunctionPatterns() {
		for (let e = 0; e < this.size; e++) this.setFunctionModule(6, e, e % 2 == 0, U.Timing), this.setFunctionModule(e, 6, e % 2 == 0, U.Timing);
		this.drawFinderPattern(3, 3), this.drawFinderPattern(this.size - 4, 3), this.drawFinderPattern(3, this.size - 4);
		let e = this.getAlignmentPatternPositions(), t = e.length;
		for (let n = 0; n < t; n++) for (let r = 0; r < t; r++) n === 0 && r === 0 || n === 0 && r === t - 1 || n === t - 1 && r === 0 || this.drawAlignmentPattern(e[n], e[r]);
		this.drawFormatBits(0), this.drawVersion();
	}
	drawFormatBits(e) {
		let t = this.ecc[1] << 3 | e, n = t;
		for (let e = 0; e < 10; e++) n = n << 1 ^ (n >>> 9) * 1335;
		let r = (t << 10 | n) ^ 21522;
		for (let e = 0; e <= 5; e++) this.setFunctionModule(8, e, Y(r, e));
		this.setFunctionModule(8, 7, Y(r, 6)), this.setFunctionModule(8, 8, Y(r, 7)), this.setFunctionModule(7, 8, Y(r, 8));
		for (let e = 9; e < 15; e++) this.setFunctionModule(14 - e, 8, Y(r, e));
		for (let e = 0; e < 8; e++) this.setFunctionModule(this.size - 1 - e, 8, Y(r, e));
		for (let e = 8; e < 15; e++) this.setFunctionModule(8, this.size - 15 + e, Y(r, e));
		this.setFunctionModule(8, this.size - 8, !0);
	}
	drawVersion() {
		if (this.version < 7) return;
		let e = this.version;
		for (let t = 0; t < 12; t++) e = e << 1 ^ (e >>> 11) * 7973;
		let t = this.version << 12 | e;
		for (let e = 0; e < 18; e++) {
			let n = Y(t, e), r = this.size - 11 + e % 3, i = Math.floor(e / 3);
			this.setFunctionModule(r, i, n), this.setFunctionModule(i, r, n);
		}
	}
	drawFinderPattern(e, t) {
		for (let n = -4; n <= 4; n++) for (let r = -4; r <= 4; r++) {
			let i = Math.max(Math.abs(r), Math.abs(n)), a = e + r, o = t + n;
			a >= 0 && a < this.size && o >= 0 && o < this.size && this.setFunctionModule(a, o, i !== 2 && i !== 4, U.Position);
		}
	}
	drawAlignmentPattern(e, t) {
		for (let n = -2; n <= 2; n++) for (let r = -2; r <= 2; r++) this.setFunctionModule(e + r, t + n, Math.max(Math.abs(r), Math.abs(n)) !== 1, U.Alignment);
	}
	setFunctionModule(e, t, n, r = U.Function) {
		this.modules[t][e] = n, this.types[t][e] = r;
	}
	addEccAndInterleave(e) {
		let t = this.version, n = this.ecc;
		if (e.length !== Q(t, n)) throw RangeError("Invalid argument");
		let r = Se[n[0]][t], i = xe[n[0]][t], a = Math.floor(Z(t) / 8), o = r - a % r, s = Math.floor(a / r), c = [], l = Ie(i);
		for (let t = 0, n = 0; t < r; t++) {
			let r = e.slice(n, n + s - i + (t < o ? 0 : 1));
			n += r.length;
			let a = Le(r, l);
			t < o && r.push(0), c.push(r.concat(a));
		}
		let u = [];
		for (let e = 0; e < c[0].length; e++) c.forEach((t, n) => {
			(e !== s - i || n >= o) && u.push(t[e]);
		});
		return u;
	}
	drawCodewords(e) {
		if (e.length !== Math.floor(Z(this.version) / 8)) throw RangeError("Invalid argument");
		let t = 0;
		for (let n = this.size - 1; n >= 1; n -= 2) {
			n === 6 && (n = 5);
			for (let r = 0; r < this.size; r++) for (let i = 0; i < 2; i++) {
				let a = n - i, o = n + 1 & 2 ? r : this.size - 1 - r;
				!this.types[o][a] && t < e.length * 8 && (this.modules[o][a] = Y(e[t >>> 3], 7 - (t & 7)), t++);
			}
		}
	}
	applyMask(e) {
		if (e < 0 || e > 7) throw RangeError("Mask value out of range");
		for (let t = 0; t < this.size; t++) for (let n = 0; n < this.size; n++) {
			let r;
			switch (e) {
				case 0:
					r = (n + t) % 2 == 0;
					break;
				case 1:
					r = t % 2 == 0;
					break;
				case 2:
					r = n % 3 == 0;
					break;
				case 3:
					r = (n + t) % 3 == 0;
					break;
				case 4:
					r = (Math.floor(n / 3) + Math.floor(t / 2)) % 2 == 0;
					break;
				case 5:
					r = n * t % 2 + n * t % 3 == 0;
					break;
				case 6:
					r = (n * t % 2 + n * t % 3) % 2 == 0;
					break;
				case 7:
					r = ((n + t) % 2 + n * t % 3) % 2 == 0;
					break;
				default: throw Error("Unreachable");
			}
			!this.types[t][n] && r && (this.modules[t][n] = !this.modules[t][n]);
		}
	}
	getPenaltyScore() {
		let e = 0;
		for (let t = 0; t < this.size; t++) {
			let n = !1, r = 0, i = [
				0,
				0,
				0,
				0,
				0,
				0,
				0
			];
			for (let a = 0; a < this.size; a++) this.modules[t][a] === n ? (r++, r === 5 ? e += ve : r > 5 && e++) : (this.finderPenaltyAddHistory(r, i), n || (e += this.finderPenaltyCountPatterns(i) * q), n = this.modules[t][a], r = 1);
			e += this.finderPenaltyTerminateAndCount(n, r, i) * q;
		}
		for (let t = 0; t < this.size; t++) {
			let n = !1, r = 0, i = [
				0,
				0,
				0,
				0,
				0,
				0,
				0
			];
			for (let a = 0; a < this.size; a++) this.modules[a][t] === n ? (r++, r === 5 ? e += ve : r > 5 && e++) : (this.finderPenaltyAddHistory(r, i), n || (e += this.finderPenaltyCountPatterns(i) * q), n = this.modules[a][t], r = 1);
			e += this.finderPenaltyTerminateAndCount(n, r, i) * q;
		}
		for (let t = 0; t < this.size - 1; t++) for (let n = 0; n < this.size - 1; n++) {
			let r = this.modules[t][n];
			r === this.modules[t][n + 1] && r === this.modules[t + 1][n] && r === this.modules[t + 1][n + 1] && (e += ye);
		}
		let t = 0;
		for (let e of this.modules) t = e.reduce((e, t) => e + +!!t, t);
		let n = this.size * this.size, r = Math.ceil(Math.abs(t * 20 - n * 10) / n) - 1;
		return e += r * be, e;
	}
	getAlignmentPatternPositions() {
		if (this.version === 1) return [];
		{
			let e = Math.floor(this.version / 7) + 2, t = this.version === 32 ? 26 : Math.ceil((this.version * 4 + 4) / (e * 2 - 2)) * 2, n = [6];
			for (let r = this.size - 7; n.length < e; r -= t) n.splice(1, 0, r);
			return n;
		}
	}
	finderPenaltyCountPatterns(e) {
		let t = e[1], n = t > 0 && e[2] === t && e[3] === t * 3 && e[4] === t && e[5] === t;
		return (n && e[0] >= t * 4 && e[6] >= t ? 1 : 0) + (n && e[6] >= t * 4 && e[0] >= t ? 1 : 0);
	}
	finderPenaltyTerminateAndCount(e, t, n) {
		return e && (this.finderPenaltyAddHistory(t, n), t = 0), t += this.size, this.finderPenaltyAddHistory(t, n), this.finderPenaltyCountPatterns(n);
	}
	finderPenaltyAddHistory(e, t) {
		t[0] === 0 && (e += this.size), t.pop(), t.unshift(e);
	}
};
function J(e, t, n) {
	if (t < 0 || t > 31 || e >>> t) throw RangeError("Value out of range");
	for (let r = t - 1; r >= 0; r--) n.push(e >>> r & 1);
}
function Y(e, t) {
	return !!(e >>> t & 1);
}
var X = class {
	constructor(e, t, n) {
		if (this.mode = e, this.numChars = t, this.bitData = n, t < 0) throw RangeError("Invalid argument");
		this.bitData = n.slice();
	}
	getData() {
		return this.bitData.slice();
	}
}, we = [
	1,
	10,
	12,
	14
], Te = [
	2,
	9,
	11,
	13
], Ee = [
	4,
	8,
	16,
	16
];
function De(e, t) {
	return e[Math.floor((t + 7) / 17) + 1];
}
function Oe(e) {
	let t = [];
	for (let n of e) J(n, 8, t);
	return new X(Ee, e.length, t);
}
function ke(e) {
	if (!Me(e)) throw RangeError("String contains non-numeric characters");
	let t = [];
	for (let n = 0; n < e.length;) {
		let r = Math.min(e.length - n, 3);
		J(Number.parseInt(e.substring(n, n + r), 10), r * 3 + 1, t), n += r;
	}
	return new X(we, e.length, t);
}
function Ae(e) {
	if (!Ne(e)) throw RangeError("String contains unencodable characters in alphanumeric mode");
	let t = [], n = 0;
	for (; n + 2 <= e.length; n += 2) {
		let r = W.indexOf(e.charAt(n)) * 45;
		r += W.indexOf(e.charAt(n + 1)), J(r, 11, t);
	}
	return n < e.length && J(W.indexOf(e.charAt(n)), 6, t), new X(Te, e.length, t);
}
function je(e) {
	return e === "" ? [] : Me(e) ? [ke(e)] : Ne(e) ? [Ae(e)] : [Oe(Fe(e))];
}
function Me(e) {
	return ge.test(e);
}
function Ne(e) {
	return _e.test(e);
}
function Pe(e, t) {
	let n = 0;
	for (let r of e) {
		let e = De(r.mode, t);
		if (r.numChars >= 1 << e) return Infinity;
		n += 4 + e + r.bitData.length;
	}
	return n;
}
function Fe(e) {
	e = encodeURI(e);
	let t = [];
	for (let n = 0; n < e.length; n++) e.charAt(n) === "%" ? (t.push(Number.parseInt(e.substring(n + 1, n + 3), 16)), n += 2) : t.push(e.charCodeAt(n));
	return t;
}
function Z(e) {
	if (e < G || e > K) throw RangeError("Version number out of range");
	let t = (16 * e + 128) * e + 64;
	if (e >= 2) {
		let n = Math.floor(e / 7) + 2;
		t -= (25 * n - 10) * n - 55, e >= 7 && (t -= 36);
	}
	return t;
}
function Q(e, t) {
	return Math.floor(Z(e) / 8) - xe[t[0]][e] * Se[t[0]][e];
}
function Ie(e) {
	if (e < 1 || e > 255) throw RangeError("Degree out of range");
	let t = [];
	for (let n = 0; n < e - 1; n++) t.push(0);
	t.push(1);
	let n = 1;
	for (let r = 0; r < e; r++) {
		for (let e = 0; e < t.length; e++) t[e] = $(t[e], n), e + 1 < t.length && (t[e] ^= t[e + 1]);
		n = $(n, 2);
	}
	return t;
}
function Le(e, t) {
	let n = t.map((e) => 0);
	for (let r of e) {
		let e = r ^ n.shift();
		n.push(0), t.forEach((t, r) => n[r] ^= $(t, e));
	}
	return n;
}
function $(e, t) {
	if (e >>> 8 || t >>> 8) throw RangeError("Byte out of range");
	let n = 0;
	for (let r = 7; r >= 0; r--) n = n << 1 ^ (n >>> 7) * 285, n ^= (t >>> r & 1) * e;
	return n;
}
function Re(e, t, n = 1, r = 40, i = -1, a = !0) {
	if (!(G <= n && n <= r && r <= K) || i < -1 || i > 7) throw RangeError("Invalid value");
	let o, s;
	for (o = n;; o++) {
		let n = Q(o, t) * 8, i = Pe(e, o);
		if (i <= n) {
			s = i;
			break;
		}
		if (o >= r) throw RangeError("Data too long");
	}
	for (let e of [
		fe,
		pe,
		me
	]) a && s <= Q(o, e) * 8 && (t = e);
	let c = [];
	for (let t of e) {
		J(t.mode[0], 4, c), J(t.numChars, De(t.mode, o), c);
		for (let e of t.getData()) c.push(e);
	}
	let l = Q(o, t) * 8;
	J(0, Math.min(4, l - c.length), c), J(0, (8 - c.length % 8) % 8, c);
	for (let e = 236; c.length < l; e ^= 253) J(e, 8, c);
	let u = Array.from({ length: Math.ceil(c.length / 8) }, () => 0);
	return c.forEach((e, t) => u[t >>> 3] |= e << 7 - (t & 7)), new Ce(o, t, u, i);
}
function ze(e, t) {
	let { ecc: n = "L", boostEcc: r = !1, minVersion: i = 1, maxVersion: a = 40, maskPattern: o = -1, border: s = 1 } = t || {}, c = typeof e == "string" ? je(e) : Array.isArray(e) ? [Oe(e)] : void 0;
	if (!c) throw Error(`uqr only supports encoding string and binary data, but got: ${typeof e}`);
	let l = Re(c, he[n], i, a, o, r), u = Be({
		version: l.version,
		maskPattern: l.mask,
		size: l.size,
		data: l.modules,
		types: l.types
	}, s);
	return t?.invert && (u.data = u.data.map((e) => e.map((e) => !e))), t?.onEncoded?.(u), u;
}
function Be(e, t = 1) {
	if (!t) return e;
	let { size: n } = e, r = n + t * 2;
	e.size = r, e.data.forEach((e) => {
		for (let n = 0; n < t; n++) e.unshift(!1), e.push(!1);
	});
	for (let n = 0; n < t; n++) e.data.unshift(Array.from({ length: r }, (e) => !1)), e.data.push(Array.from({ length: r }, (e) => !1));
	let i = U.Border;
	e.types.forEach((e) => {
		for (let n = 0; n < t; n++) e.unshift(i), e.push(i);
	});
	for (let n = 0; n < t; n++) e.types.unshift(Array.from({ length: r }, (e) => i)), e.types.push(Array.from({ length: r }, (e) => i));
	return e;
}
function Ve(e) {
	return e.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
function He(e, t = {}) {
	let n = ze(e, t), { pixelSize: r = 10, whiteColor: i = "white", blackColor: a = "black" } = t, o = n.size * r, s = n.size * r, c = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${s} ${o}">`, l = [];
	for (let e = 0; e < n.size; e++) for (let t = 0; t < n.size; t++) {
		let i = t * r, a = e * r;
		n.data[e][t] && l.push(`M${i},${a}h${r}v${r}h-${r}z`);
	}
	return c += `<rect fill="${Ve(i)}" width="${s}" height="${o}"/>`, c += `<path fill="${Ve(a)}" d="${l.join("")}"/>`, c += "</svg>", c;
}
// src/components/two-factor/GuardianQrCode.vue?vue&type=script&setup=true&lang.ts
var Ue = ["aria-label", "innerHTML"], We = /* @__PURE__ */ v({
	__name: "GuardianQrCode",
	props: {
		value: {},
		label: {}
	},
	setup(e) {
		let t = e, n = d(() => He(t.value, { border: 2 }));
		return (t, r) => (x(), m("div", {
			class: "size-48 rounded bg-white p-1",
			role: "img",
			"aria-label": e.label,
			"data-testid": "guardian-qr",
			innerHTML: E(n)
		}, null, 8, Ue));
	}
}), Ge = {
	class: "space-y-3",
	"data-testid": "guardian-totp"
}, Ke = { class: "font-medium" }, qe = {
	key: 1,
	class: "space-y-3"
}, Je = { class: "text-sm text-muted" }, Ye = { class: "text-sm" }, Xe = {
	class: "break-all font-mono",
	"data-testid": "guardian-totp-secret"
}, Ze = /* @__PURE__ */ v({
	__name: "GuardianTotpEnrol",
	props: { client: {} },
	emits: ["confirmed"],
	setup(n, { emit: r }) {
		let a = n, o = r, { t: s } = I(), c = t(), l = t(), u = S(""), d = S(""), f = S(null), p = S(null);
		function v() {
			f.value = null, p.value = null, d.value = "";
		}
		async function C() {
			await c.run(async () => {
				let e = i(await a.client.POST("/two-factor/totp", { body: { label: u.value.trim() || null } })).data;
				f.value = e.secret, p.value = e.otpauth_uri;
			});
		}
		async function w() {
			let e = null, t = await l.run(async () => {
				e = i(await a.client.POST("/two-factor/totp/confirm", { body: { code: d.value.trim() } })).data.recovery_codes ?? null;
			});
			d.value = "", t && (v(), o("confirmed", e));
		}
		return b(v), (t, n) => {
			let r = P, i = N, a = j;
			return x(), m("div", Ge, [
				h("h3", Ke, T(E(s)("guardian.two_factor.totp.title")), 1),
				_(e, { action: E(c) }, null, 8, ["action"]),
				E(p) ? (x(), m("div", qe, [
					h("p", Je, T(E(s)("guardian.two_factor.totp.scan")), 1),
					_(We, {
						value: E(p),
						label: E(s)("guardian.two_factor.totp.qr_label")
					}, null, 8, ["value", "label"]),
					h("p", Ye, [g(T(E(s)("guardian.two_factor.totp.manual")) + " ", 1), h("code", Xe, T(E(f)), 1)]),
					_(e, { action: E(l) }, null, 8, ["action"]),
					h("form", {
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "guardian-totp-confirm",
						onSubmit: k(w, ["prevent"])
					}, [_(i, {
						label: E(s)("guardian.two_factor.fields.code"),
						error: E(l).error("code"),
						required: ""
					}, {
						default: O(() => [_(r, {
							modelValue: E(d),
							"onUpdate:modelValue": n[1] ||= (e) => y(d) ? d.value = e : null,
							autocomplete: "one-time-code",
							inputmode: "numeric",
							"data-testid": "guardian-totp-code"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, ["label", "error"]), _(a, {
						type: "submit",
						loading: E(l).busy.value,
						"data-testid": "guardian-totp-submit"
					}, {
						default: O(() => [g(T(E(s)("guardian.two_factor.confirm")), 1)]),
						_: 1
					}, 8, ["loading"])], 32)
				])) : (x(), m("form", {
					key: 0,
					class: "flex flex-wrap items-end gap-2",
					"data-testid": "guardian-totp-start",
					onSubmit: k(C, ["prevent"])
				}, [_(i, {
					label: E(s)("guardian.two_factor.fields.label"),
					error: E(c).error("label")
				}, {
					default: O(() => [_(r, {
						modelValue: E(u),
						"onUpdate:modelValue": n[0] ||= (e) => y(u) ? u.value = e : null,
						"data-testid": "guardian-totp-label"
					}, null, 8, ["modelValue"])]),
					_: 1
				}, 8, ["label", "error"]), _(a, {
					type: "submit",
					loading: E(c).busy.value,
					"data-testid": "guardian-totp-begin"
				}, {
					default: O(() => [g(T(E(s)("guardian.two_factor.totp.begin")), 1)]),
					_: 1
				}, 8, ["loading"])], 32))
			]);
		};
	}
}), Qe = {
	class: "space-y-4",
	"data-testid": "guardian-two-factor-methods"
}, $e = {
	key: 1,
	class: "space-y-2"
}, et = { class: "font-medium" }, tt = { class: "divide-y divide-default text-sm" }, nt = ["data-testid"], rt = { class: "text-muted" }, it = {
	key: 0,
	class: "py-2 text-muted"
}, at = {
	key: 5,
	class: "space-y-2",
	"data-testid": "guardian-recovery"
}, ot = { class: "font-medium" }, st = {
	class: "text-sm",
	"data-testid": "guardian-recovery-remaining"
}, ct = /* @__PURE__ */ v({
	__name: "GuardianTwoFactorMethods",
	props: {
		client: {},
		status: {},
		setup: {
			type: Boolean,
			default: !1
		}
	},
	emits: ["changed", "confirmed"],
	setup(r, { emit: s }) {
		let c = r, l = s, { t: v } = I(), y = t(), w = S(null), D = d(() => o(c.status)), k = d(() => D.value.filter((e) => e === "email" || e === "sms")), A = d(() => a(c.status));
		function M(e) {
			c.setup ? l("confirmed", e) : (w.value = e && e.length > 0 ? e : null, l("changed"));
		}
		async function N() {
			window.confirm(v("guardian.two_factor.recovery.confirm_regenerate")) && (await y.run(async () => {
				w.value = i(await c.client.POST("/two-factor/recovery-codes")).data.recovery_codes;
			}), l("changed"));
		}
		return b(() => {
			w.value = null;
		}), (t, i) => {
			let a = j;
			return x(), m("div", Qe, [
				E(w) ? (x(), f(ue, {
					key: 0,
					codes: E(w),
					onDone: i[0] ||= (e) => w.value = null
				}, null, 8, ["codes"])) : p("", !0),
				r.setup ? p("", !0) : (x(), m("section", $e, [h("h3", et, T(E(v)("guardian.two_factor.methods.title")), 1), h("ul", tt, [(x(!0), m(u, null, C(E(A), (e) => (x(), m("li", {
					key: e.id,
					class: "flex flex-wrap items-center justify-between gap-2 py-2",
					"data-testid": `guardian-method-${e.id}`
				}, [h("span", null, [
					h("strong", null, T(E(v)(`guardian.two_factor.types.${e.type}`)), 1),
					g(" " + T(e.label ?? e.destination ?? "") + " ", 1),
					h("span", rt, T(E(n)(e.last_used_at) ?? E(v)("guardian.two_factor.never_used")), 1)
				]), _(R, {
					client: r.client,
					kind: "method",
					id: e.id,
					name: E(v)(`guardian.two_factor.types.${e.type}`),
					onRemoved: i[1] ||= (e) => l("changed")
				}, null, 8, [
					"client",
					"id",
					"name"
				])], 8, nt))), 128)), E(A).length === 0 ? (x(), m("li", it, T(E(v)("guardian.two_factor.methods.none")), 1)) : p("", !0)])])),
				E(D).includes("totp") ? (x(), f(Ze, {
					key: 2,
					client: r.client,
					onConfirmed: M
				}, null, 8, ["client"])) : p("", !0),
				E(k).length > 0 ? (x(), f(V, {
					key: 3,
					client: r.client,
					types: E(k),
					onConfirmed: M
				}, null, 8, ["client", "types"])) : p("", !0),
				E(D).includes("passkey") ? (x(), f(H, {
					key: 4,
					client: r.client,
					available: r.status.passkeys_available,
					manage: !r.setup,
					onConfirmed: M,
					onChanged: i[2] ||= (e) => l("changed")
				}, null, 8, [
					"client",
					"available",
					"manage"
				])) : p("", !0),
				!r.setup && r.status.enabled ? (x(), m("section", at, [
					h("h3", ot, T(E(v)("guardian.two_factor.recovery.title")), 1),
					h("p", st, T(E(v)("guardian.two_factor.recovery.remaining", { count: r.status.recovery_codes_remaining })), 1),
					_(e, { action: E(y) }, null, 8, ["action"]),
					_(a, {
						color: "neutral",
						variant: "soft",
						icon: "i-lucide-refresh-cw",
						loading: E(y).busy.value,
						"data-testid": "guardian-recovery-regenerate",
						onClick: N
					}, {
						default: O(() => [g(T(E(v)("guardian.two_factor.recovery.regenerate")), 1)]),
						_: 1
					}, 8, ["loading"])
				])) : p("", !0)
			]);
		};
	}
});
export { ue as n, ct as t };
