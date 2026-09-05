/**
 * @internal @brnshkr/config/commitlint
 */

/* eslint-disable brnshkr/boolish-prefix -- Public option keys are named after the module they toggle, so they carry no boolish prefix */

import type { TenseOptions } from 'commitlint-plugin-tense/dist/library/ensure-tense';
import type { Config } from './config';

export interface ConfigOptions {
  /**
   * Enables `@commitlint/config-conventional`.
   *
   * @default `Enabled when "@commitlint/config-conventional" is installed.`
   *
   * @see https://github.com/conventional-changelog/commitlint
   */
  conventional: boolean;

  /**
   * Enables `commitlint-plugin-function-rules`.
   *
   * @default `Enabled when "commitlint-plugin-function-rules" is installed.`
   *
   * @see https://github.com/gabrielcolson/commitlint-plugin-function-rules
   */
  functions: boolean;

  /**
   * Enables `commitlint-plugin-tense`.
   *
   * @default `Enabled when "commitlint-plugin-tense" is installed.`
   *
   * @see https://github.com/actuallyleon/commitlint-plugin-tense
   */
  tense: boolean | Partial<TenseOptions>;
}

export type ResolvedOptions = ConfigOptions & Config;
export type UserOptions = Partial<ConfigOptions> & Config;

/* eslint-enable brnshkr/boolish-prefix -- Restore rule */
