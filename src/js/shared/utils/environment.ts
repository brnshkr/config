/**
 * @internal @brnshkr/config
 */

import type { Maybe } from '#shared/types/core.ts';

/* eslint-disable-next-line node/no-process-env, security/detect-object-injection, ts/no-unnecessary-condition -- This is the only place in the application that is allowed to read the environment directly */
export const getEnvironmentValue = (key: string): Maybe<string> => (import.meta.env ?? process.env)[key];
