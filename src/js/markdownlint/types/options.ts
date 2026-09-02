/**
 * @internal @brnshkr/config/markdownlint
 */

/* eslint-disable brnshkr/boolish-prefix -- Public option keys are named after the module they toggle, so they carry no boolish prefix */

import type { Config } from './config';

export interface ConfigOptions {
  /**
   * Enables `@github/markdownlint-github`.
   *
   * @default `Enabled when "@github/markdownlint-github" is installed.`
   *
   * @see https://github.com/github/markdownlint-github
   */
  github: boolean;

  /**
   * Enables `markdownlint-rule-relative-links` and `markdownlint-rule-no-trailing-slash-in-links`.
   *
   * @default `Enabled when "markdownlint-rule-relative-links" or "markdownlint-rule-no-trailing-slash-in-links" is installed.`
   *
   * @see https://github.com/theoludwig/markdownlint-rule-relative-links
   * @see https://github.com/xiaogaozi/markdownlint-rule-no-trailing-slash-in-links
   */
  links: boolean;

  /**
   * Enables `markdownlint-rule-search-replace`.
   *
   * @default `Enabled when "markdownlint-rule-search-replace" is installed.`
   *
   * @see https://github.com/OnkarRuikar/markdownlint-rule-search-replace
   */
  search: boolean;

  /**
   * Enables `@hongminhee/markdownlint-rules`.
   *
   * @default `Enabled when "@hongminhee/markdownlint-rules" is installed.`
   *
   * @see https://github.com/dahlia/markdownlint-rules
   */
  style: boolean;

  /**
   * Enables `markdownlint-rule-table-format`.
   *
   * @default `Enabled when "markdownlint-rule-table-format" is installed.`
   *
   * @see https://github.com/swirle13/markdownlint-rule-table-format
   */
  tables: boolean;
}

export type ResolvedOptions = ConfigOptions & Config;
export type UserOptions = Partial<ConfigOptions> & Config;

/* eslint-enable brnshkr/boolish-prefix -- Restore rule */
