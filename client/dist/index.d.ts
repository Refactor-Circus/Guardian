import { type Client, type ClientOptions } from 'openapi-fetch';
import type { components, paths } from '../src/schema.js';
export type { components, operations, paths } from '../src/schema.js';
/**
 * Options for `createGuardianClient`: openapi-fetch's own (`fetch`,
 * `headers`, `credentials`, ...), with `baseUrl` required.
 */
export type GuardianClientOptions = ClientOptions & {
    baseUrl: string;
};
/** A typed fetch client for Guardian's JSON API. */
export type GuardianClient = Client<paths>;
/** Every schema of the API (resources and request bodies), by name. */
export type GuardianSchemas = components['schemas'];
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
export declare function createGuardianClient(options: GuardianClientOptions): GuardianClient;
