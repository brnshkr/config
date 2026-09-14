/**
 * @internal @brnshkr/config
 */

import { packageFullName } from './package-json';

export const log = (message: string): void => {
  // eslint-disable-next-line no-console -- This is the only place in the application that is allowed to use the console directly
  console.error(`[${packageFullName}] ${message}`);
};
