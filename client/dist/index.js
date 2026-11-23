import createClient from 'openapi-fetch';
/**
 * Create a client for Guardian's JSON API.
 *
 * `baseUrl` is the host's `guardian.routes.prefix` URL, for example
 * `https://app.test/guardian`: the document's paths are relative to it.
 *
 * ```ts
 * const guardian = createGuardianClient({ baseUrl: '/guardian', credentials: 'include' });
 * const { data } = await guardian.GET('/two-factor');
 * ```
 */
export function createGuardianClient(options) {
    return createClient(options);
}
