// src/components/ui.ts
var e = "__none";
function t(t) {
	return t === "" ? e : t;
}
function n(e) {
	let t = e == null ? "" : String(e);
	return t === "__none" ? "" : t;
}
function r(e, t, n) {
	return n === !0 ? [.../* @__PURE__ */ new Set([...e, t])] : e.filter((e) => e !== t);
}
export { n as i, t as n, r, e as t };
