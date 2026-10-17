/**
 * @internal @brnshkr/config/eslint
 */

import type { CSSLanguageOptions } from '@eslint/css';
import type { Linter } from 'eslint';
import type { Config, TsEslintParserOptions } from '#eslint/types/config.ts';
import type { CssDialect } from '#shared/utils/dialect.ts';
import type { packageOrganization } from '#shared/utils/package-json.ts';

/* eslint-disable brnshkr/boolish-prefix -- Public option keys mirror the upstream names they configure, so they carry no boolish prefix */
export interface TailwindOptions {
  /**
   * Directory Tailwind CSS and its configuration are resolved from.
   *
   * @default undefined
   *
   * @see https://github.com/schoero/eslint-plugin-better-tailwindcss/blob/main/docs/settings/settings.md#cwd
   */
  cwd: string;

  /**
   * Treats the component classes a project defines in CSS as known.
   *
   * @default true
   *
   * @see https://github.com/schoero/eslint-plugin-better-tailwindcss/blob/main/docs/settings/settings.md#detectcomponentclasses
   */
  detectComponentClasses: boolean;

  /**
   * Path to the CSS file holding the Tailwind CSS theme.
   *
   * @default undefined
   *
   * @see https://github.com/schoero/eslint-plugin-better-tailwindcss/blob/main/docs/settings/settings.md#entrypoint
   */
  entryPoint: string;

  /**
   * How a message shows a class list.
   *
   * @default `"visual", or "compact" in CI.`
   *
   * @see https://github.com/schoero/eslint-plugin-better-tailwindcss/blob/main/docs/settings/settings.md#messagestyle
   */
  messageStyle: 'compact' | 'raw' | 'visual';

  /**
   * Root font size in pixels.
   *
   * @default 16
   *
   * @see https://github.com/schoero/eslint-plugin-better-tailwindcss/blob/main/docs/settings/settings.md#rootfontsize
   */
  rootFontSize: number;

  /**
   * Path to the JavaScript configuration of Tailwind CSS 3.
   *
   * @default undefined
   *
   * @see https://github.com/schoero/eslint-plugin-better-tailwindcss/blob/main/docs/settings/settings.md#tailwindconfig
   */
  tailwindConfig: string;

  /**
   * Path to the TypeScript configuration file, used to resolve path aliases.
   *
   * @default `The tsconfig.json file the "typescript" module uses.`
   *
   * @see https://github.com/schoero/eslint-plugin-better-tailwindcss/blob/main/docs/settings/settings.md#tsconfig
   */
  tsconfig: string;
}

export interface CssOptions {
  /**
   * Additional syntax definitions, e.g. project specific at-rules.
   *
   * @default undefined
   *
   * @see https://github.com/eslint/css#configuring-custom-syntax
   */
  customSyntax: CSSLanguageOptions['customSyntax'];

  /**
   * Additional CSS syntax, e.g. project specific at-rules and functions.
   *
   * @default `Tailwind CSS's syntax when the "tailwind" module is enabled.`
   */
  dialect: Partial<CssDialect>;

  /**
   * Enables tolerant parsing of preludes the CSS parser cannot read.
   *
   * @default `Enabled when the "tailwind" module is enabled.`
   */
  tolerant: boolean;
}
/* eslint-enable brnshkr/boolish-prefix */

export interface NodeOptions {
  /**
   * The node version to use.
   *
   * @default process.versions.node
   */
  version: string;
}

export interface MarkdownOptions {
  /**
   * The language to use for parsing markdown files.
   *
   * @default 'markdown/commonmark'
   */
  language: `markdown/${'commonmark' | 'gfm'}`;

  /**
   * Enables parsing of frontmatter in markdown files.
   *
   * @default false
   */
  frontmatter: false | 'json' | 'toml' | 'yaml';
}

export interface IgnoresAndFiles {
  /**
   * An array of glob patterns indicating the files include in the linting process.
   *
   * @default []
   */
  files?: Linter.Config['files'];

  /**
   * An array of glob patterns indicating files to exclude from the linting process.
   *
   * @default []
   */
  // eslint-disable-next-line brnshkr/boolish-prefix -- Mirrors ESLint's own `ignores` config key
  ignores?: Linter.Config['ignores'];
}

export interface TypeAwareOptions extends IgnoresAndFiles {
  /**
   * Path to the TypeScript configuration file.
   *
   * @default undefined
   */
  tsconfig?: string;
}

export interface TypescriptOptions extends IgnoresAndFiles {
  /**
   * Additional file extensions to parse.
   *
   * @default []
   */
  extraFileExtensions: string[];

  /**
   * Additional parser options for TypeScript.
   *
   * @default {}
   */
  parserOptions: TsEslintParserOptions;

  /**
   * Enables type aware rules of `@typescript-eslint/eslint-plugin`.
   *
   * By default the path `./tsconfig.json` is assumed for the TypeScript configuration file.
   * If it exists, type aware rules will be enabled.
   * Optionally, you can provide a custom path or even further customize
   * the type aware options by passing an object.
   *
   * @default `Enabled when the the configured tsconfig.json file exists`
   *
   * @see https://typescript-eslint.io/linting/typed-linting
   */
  typeAware: boolean | string | TypeAwareOptions;
}

/* eslint-disable brnshkr/boolish-prefix -- Public option keys are named after the module they toggle, so they carry no boolish prefix */
export interface ConfigOptions {
  /**
   * Enables builtin rules.
   *
   * @default true
   */
  [packageOrganization]: boolean;

  /**
   * Enables `@eslint-community/eslint-plugin-eslint-comments`.
   *
   * @default `Enabled when "@eslint-community/eslint-plugin-eslint-comments" is installed.`
   *
   * @see https://github.com/eslint-community/eslint-plugin-eslint-comments
   */
  comments: boolean;

  /**
   * Enables `@eslint/css`.
   *
   * @default `Enabled when "@eslint/css" is installed.`
   *
   * @see https://github.com/eslint/css
   */
  css: boolean | Partial<CssOptions>;

  /**
   * Enables `@html-eslint/eslint-plugin`.
   *
   * @default `Enabled when "@html-eslint/eslint-plugin" is installed.`
   *
   * @see https://github.com/yeonjuan/html-eslint
   * @see https://github.com/BenoitZugmeyer/eslint-plugin-html
   */
  html: boolean;

  /**
   * Enables `eslint-plugin-import-x`.
   *
   * @default `Enabled when "eslint-plugin-import-x" or "eslint-plugin-antfu" are installed.`
   *
   * @see https://github.com/un-ts/eslint-plugin-import-x
   * @see https://github.com/antfu/eslint-plugin-antfu
   */
  import: boolean;

  /**
   * Enables `eslint-plugin-jsdoc`.
   *
   * @default `Enabled when "eslint-plugin-jsdoc" is installed.`
   *
   * @see https://github.com/gajus/eslint-plugin-jsdoc
   */
  jsdoc: boolean;

  /**
   * Enables `@eslint/json`.
   *
   * @default `Enabled when "@eslint/json" is installed.`
   *
   * @see https://github.com/eslint/json
   */
  json: boolean;

  /**
   * Enables `@eslint/markdown`.
   *
   * @default `Enabled when "@eslint/markdown" is installed.`
   *
   * @see https://github.com/eslint/markdown
   */
  markdown: boolean | Partial<MarkdownOptions>;

  /**
   * Enables `eslint-plugin-n`.
   *
   * @default `Enabled when "eslint-plugin-n" is installed.`
   *
   * @see https://github.com/eslint-community/eslint-plugin-n
   */
  node: boolean | Partial<NodeOptions>;

  /**
   * Enables `eslint-plugin-perfectionist`.
   *
   * @default `Enabled when "eslint-plugin-perfectionist" is installed.`
   *
   * @see https://github.com/azat-io/eslint-plugin-perfectionist
   */
  perfectionist: boolean;

  /**
   * Enables `eslint-plugin-regexp`.
   *
   * @default `Enabled when "eslint-plugin-regexp" is installed.`
   *
   * @see https://github.com/ota-meshi/eslint-plugin-regexp
   */
  regexp: boolean;

  /**
   * Enables `eslint-plugin-security`.
   *
   * @default `Enabled when "eslint-plugin-security" is installed.`
   *
   * @see https://github.com/eslint-community/eslint-plugin-security
   */
  security: boolean;

  /**
   * Enables `@stylistic/eslint-plugin`.
   *
   * @default `Enabled when "@stylistic/eslint-plugin" is installed.`
   *
   * @see https://github.com/eslint-stylistic/eslint-stylistic
   */
  style: boolean;

  /**
   * Enables `eslint-plugin-svelte`.
   *
   * @default `Enabled when "eslint-plugin-svelte" is installed.`
   *
   * @see https://github.com/sveltejs/eslint-plugin-svelte
   * @see https://github.com/yeonjuan/html-eslint/tree/main/packages/eslint-plugin-svelte
   */
  svelte: boolean;

  /**
   * Enables `eslint-plugin-better-tailwindcss`.
   *
   * @default `Enabled when "tailwindcss" is installed.`
   *
   * @see https://github.com/tailwindlabs/tailwindcss
   * @see https://github.com/schoero/eslint-plugin-better-tailwindcss
   */
  tailwind: boolean | Partial<TailwindOptions>;

  /**
   * Enables `@vitest/eslint-plugin`.
   *
   * @default `Enabled when "@vitest/eslint-plugin" is installed.`
   *
   * @see https://github.com/vitest-dev/eslint-plugin-vitest
   */
  test: boolean;

  /**
   * Enables `eslint-plugin-toml`.
   *
   * @default `Enabled when "eslint-plugin-toml" are installed.`
   *
   * @see https://github.com/ota-meshi/eslint-plugin-toml
   */
  toml: boolean;

  /**
   * Enables `typescript-eslint`.
   *
   * @default `Enabled when "typescript" and "typescript-eslint" are installed.`
   *
   * @see https://github.com/microsoft/TypeScript
   * @see https://github.com/typescript-eslint/typescript-eslint
   */
  typescript: boolean | Partial<TypescriptOptions>;

  /**
   * Enables `eslint-plugin-unicorn`.
   *
   * @default `Enabled when "eslint-plugin-unicorn" is installed.`
   *
   * @see https://github.com/sindresorhus/eslint-plugin-unicorn
   */
  unicorn: boolean;

  /**
   * Enables `eslint-plugin-yml`.
   *
   * @default `Enabled when "eslint-plugin-yml" are installed.`
   *
   * @see https://github.com/ota-meshi/eslint-plugin-yml
   */
  yaml: boolean;
}
/* eslint-enable brnshkr/boolish-prefix */

export type ResolvedOptions = ConfigOptions & Omit<Config, 'files'>;
export type UserOptions = Partial<ConfigOptions> & Omit<Config, 'files'>;
