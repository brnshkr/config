import type { Linter } from 'eslint';
import type { FlatGitignoreOptions } from 'eslint-config-flat-gitignore';
import type { packageOrganization } from '../../shared/utils/package-json';
import type { Config, TsEslintParserOptions } from './config';

export type GitignoreOptions = Omit<FlatGitignoreOptions, 'name'>;

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
  css: boolean;

  /**
   * Enables `eslint-config-flat-gitignore`.
   *
   * @default `Enabled when "eslint-config-flat-gitignore" is installed.`
   *
   * @see https://github.com/antfu/eslint-config-flat-gitignore
   */
  gitignore: boolean | Partial<GitignoreOptions>;

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
   */
  svelte: boolean;

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
   * @default `Enabled when "eslint-plugin-toml" and "toml-eslint-parser" are installed.`
   *
   * @see https://github.com/ota-meshi/eslint-plugin-toml
   * @see https://github.com/ota-meshi/toml-eslint-parser
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
   * @default `Enabled when "eslint-plugin-yml" and "yaml-eslint-parser" are installed.`
   *
   * @see https://github.com/ota-meshi/eslint-plugin-yml
   * @see https://github.com/ota-meshi/yaml-eslint-parser
   */
  yaml: boolean;
}

export type ResolvedOptions = ConfigOptions & Omit<Config, 'files'>;
export type UserOptions = Partial<ConfigOptions> & Omit<Config, 'files'>;
