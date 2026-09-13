/**
 * @internal @brnshkr/config/vitest
 */

/* eslint-disable brnshkr/boolish-prefix -- Public option keys are named after the module they toggle, so they carry no boolish prefix */

import type { Config } from './config';

export interface ConfigOptions {
  /**
   * Enables `happy-dom` or `jsdom`.
   *
   * @default `Enabled when "happy-dom" or "jsdom" is installed.`
   *
   * @see https://github.com/capricorn86/happy-dom
   * @see https://github.com/jsdom/jsdom
   */
  environment: boolean;

  /**
   * Collects the spelling test this package ships, so a project writes none of its own for it.
   *
   * @default `true`
   *
   * @see https://github.com/brnshkr/config/blob/master/docs/spelling.md
   */
  spelling: boolean;

  /**
   * Enables `@vitest/ui`.
   *
   * @default `Enabled when "@vitest/ui" is installed.`
   *
   * @see https://github.com/vitest-dev/vitest/tree/main/packages/ui
   */
  ui: boolean;
}

export type ResolvedOptions = ConfigOptions & Config;
export type UserOptions = Partial<ConfigOptions> & Config;

/* eslint-enable brnshkr/boolish-prefix -- Restore rule */
