import type { Config } from './config';

export interface ConfigOptions {
  /**
   * Enables `stylelint-plugin-use-baseline`.
   *
   * @default `Enabled when "stylelint-plugin-use-baseline" is installed.`
   *
   * @see https://github.com/ryo-manba/stylelint-plugin-use-baseline
   */
  baseline: boolean;

  /**
   * Enables `stylelint-plugin-defensive-css`.
   *
   * @default `Enabled when "stylelint-plugin-defensive-css" is installed.`
   *
   * @see https://github.com/yuschick/stylelint-plugin-defensive-css
   */
  defensive: boolean;

  /**
   * Enables `stylelint-config-html`.
   *
   * @default `Enabled when "stylelint-config-html" and "postcss-html" are installed.`
   *
   * @see https://github.com/ota-meshi/stylelint-config-html
   * @see https://github.com/ota-meshi/postcss-html
   */
  html: boolean;

  /**
   * Enables `stylelint-plugin-logical-css`.
   *
   * @default `Enabled when "stylelint-plugin-logical-css" is installed.`
   *
   * @see https://github.com/yuschick/stylelint-plugin-logical-css
   */
  logical: boolean;

  /**
   * Enables `stylelint-config-css-modules`.
   *
   * @default `Enabled when "stylelint-config-css-modules" is installed.`
   *
   * @see https://github.com/pascalduez/stylelint-config-css-modules
   */
  modules: boolean;

  /**
   * Enables `stylelint-use-nesting`.
   *
   * @default `Enabled when "stylelint-use-nesting" is installed.`
   *
   * @see https://github.com/csstools/stylelint-use-nesting
   */
  nesting: boolean;

  /**
   * Enables `stylelint-config-recess-order`.
   *
   * @default `Enabled when "stylelint-config-recess-order" and "stylelint-order" are installed.`
   *
   * @see https://github.com/stormwarning/stylelint-config-recess-order
   * @see https://github.com/hudochenkov/stylelint-order
   */
  order: boolean;

  /**
   * Enables `stylelint-config-standard-scss`.
   *
   * @default `Enabled when "stylelint-config-standard-scss" is installed.`
   *
   * @see https://github.com/stylelint-scss/stylelint-config-standard-scss
   */
  scss: boolean;

  /**
   * Enables `stylelint-declaration-strict-value`.
   *
   * @default `Enabled when "stylelint-declaration-strict-value" is installed.`
   *
   * @see https://github.com/AndyOGo/stylelint-declaration-strict-value
   */
  strict: boolean;

  /**
   * Enables `@stylistic/stylelint-config`.
   *
   * @default `Enabled when "@stylistic/stylelint-config" is installed.`
   *
   * @see https://github.com/stylelint-stylistic/stylelint-config
   */
  style: boolean;
}

export type ResolvedOptions = ConfigOptions & Omit<Config, 'ignorePatterns' | '_processorFunctions'>;
export type UserOptions = Partial<ConfigOptions> & Omit<Config, 'ignorePatterns' | '_processorFunctions'>;
