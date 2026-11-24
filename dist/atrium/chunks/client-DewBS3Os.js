import { pageOrigin as e, resolveIntegrationBase as t, runResponseHandlers as n, signRequest as r, useAtrium as i, useAtriumBoot as a, useAtriumShellStore as o } from "@refactor-circus/atrium";
import s from "openapi-fetch";
// ../client/src/index.ts
function c(e) {
	return s(e);
}
function l(e, n, r, i) {
	return t(e || n || "/guardian", r, i);
}
function u() {
	let t = i(), s = a(), u = o().integration("guardian")?.api_base;
	return c({
		baseUrl: l(u, s.integrations.guardian?.api_base, s.api_base, e()),
		fetch: async (e) => {
			let i = await r(e), a = await globalThis.fetch(i);
			return await n(a, i), a.status === 401 && await t.auth().onUnauthorized?.(), a;
		}
	});
}
export { u as t };
