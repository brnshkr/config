import { packageFullName } from './package-json';

/* eslint-disable no-console -- This is the only place in the application that is allowed to use the console directly */
export const log = (
  type: 'log' | 'warn' | 'error',
  message: string,
  context?: Record<string, unknown>,
): void => {
  console[type](`[${packageFullName}] ${message}`);

  if (context) {
    console[type](context);
  }
};
/* eslint-enable no-console -- Restore rule */
