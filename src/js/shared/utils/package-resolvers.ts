/**
 * @internal @brnshkr/config
 */

import { isPackageExists } from 'local-pkg';

import { interopImport } from './interop-import';

export const ESLINT_PACKAGES = <const>{
  ESLINT_CSS: '@eslint/css',
  ESLINT_IMPORT_RESOLVER_TYPESCRIPT: 'eslint-import-resolver-typescript',
  ESLINT_JSON: '@eslint/json',
  ESLINT_MARKDOWN: '@eslint/markdown',
  ESLINT_MERGE_PROCESSORS: 'eslint-merge-processors',
  ESLINT_PLUGIN_ANTFU: 'eslint-plugin-antfu',
  ESLINT_PLUGIN_ESLINT_COMMENTS: '@eslint-community/eslint-plugin-eslint-comments',
  ESLINT_PLUGIN_IMPORT_X: 'eslint-plugin-import-x',
  ESLINT_PLUGIN_JSDOC: 'eslint-plugin-jsdoc',
  ESLINT_PLUGIN_JSDOC_PROCESSOR: 'eslint-plugin-jsdoc/getJsdocProcessorPlugin.js',
  ESLINT_PLUGIN_JSONC: 'eslint-plugin-jsonc',
  ESLINT_PLUGIN_N: 'eslint-plugin-n',
  ESLINT_PLUGIN_PERFECTIONIST: 'eslint-plugin-perfectionist',
  ESLINT_PLUGIN_REGEXP: 'eslint-plugin-regexp',
  ESLINT_PLUGIN_STYLISTIC: '@stylistic/eslint-plugin',
  ESLINT_PLUGIN_SVELTE: 'eslint-plugin-svelte',
  ESLINT_PLUGIN_TOML: 'eslint-plugin-toml',
  ESLINT_PLUGIN_UNICORN: 'eslint-plugin-unicorn',
  ESLINT_PLUGIN_UNUSED_IMPORTS: 'eslint-plugin-unused-imports',
  ESLINT_PLUGIN_YML: 'eslint-plugin-yml',
  SVELTE: 'svelte',
  TAILWINDCSS: 'tailwindcss',
  TYPESCRIPT: 'typescript',
  TYPESCRIPT_ESLINT: 'typescript-eslint',
  VITEST_ESLINT_PLUGIN: '@vitest/eslint-plugin',
};

export type EslintPackage = typeof ESLINT_PACKAGES[keyof typeof ESLINT_PACKAGES];

// NOTICE: Package names must be duplicated here to allow for type inference of dynamic imports
export const ESLINT_PACKAGE_RESOLVERS = <const>{
  [ESLINT_PACKAGES.ESLINT_CSS]: async () => interopImport(
    import('@eslint/css'),
  ),
  [ESLINT_PACKAGES.ESLINT_JSON]: async () => interopImport(
    import('@eslint/json'),
  ),
  [ESLINT_PACKAGES.ESLINT_IMPORT_RESOLVER_TYPESCRIPT]: async () => interopImport(
    import('eslint-import-resolver-typescript'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_ANTFU]: async () => interopImport(
    import('eslint-plugin-antfu'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_ESLINT_COMMENTS]: async () => interopImport(
    import('@eslint-community/eslint-plugin-eslint-comments'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_IMPORT_X]: async () => interopImport(
    import('eslint-plugin-import-x'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_JSDOC]: async () => interopImport(
    import('eslint-plugin-jsdoc'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_JSONC]: async () => interopImport(
    import('eslint-plugin-jsonc'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_JSDOC_PROCESSOR]: async () => interopImport(
    import('eslint-plugin-jsdoc/getJsdocProcessorPlugin.js'),
  ),
  [ESLINT_PACKAGES.ESLINT_MARKDOWN]: async () => interopImport(
    import('@eslint/markdown'),
  ),
  [ESLINT_PACKAGES.ESLINT_MERGE_PROCESSORS]: async () => interopImport(
    import('eslint-merge-processors'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_N]: async () => interopImport(
    import('eslint-plugin-n'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_PERFECTIONIST]: async () => interopImport(
    import('eslint-plugin-perfectionist'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_REGEXP]: async () => interopImport(
    import('eslint-plugin-regexp'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_STYLISTIC]: async () => interopImport(
    import('@stylistic/eslint-plugin'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_SVELTE]: async () => interopImport(
    import('eslint-plugin-svelte'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_TOML]: async () => interopImport(
    import('eslint-plugin-toml'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_UNICORN]: async () => interopImport(
    import('eslint-plugin-unicorn'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_UNUSED_IMPORTS]: async () => interopImport(
    import('eslint-plugin-unused-imports'),
  ),
  [ESLINT_PACKAGES.ESLINT_PLUGIN_YML]: async () => interopImport(
    import('eslint-plugin-yml'),
  ),
  // Do not import, just check for existence
  [ESLINT_PACKAGES.SVELTE]: () => isPackageExists(ESLINT_PACKAGES.SVELTE),
  // Do not import, just check for existence
  [ESLINT_PACKAGES.TAILWINDCSS]: () => isPackageExists(ESLINT_PACKAGES.TAILWINDCSS),
  // Do not import, just check for existence
  [ESLINT_PACKAGES.TYPESCRIPT]: () => isPackageExists(ESLINT_PACKAGES.TYPESCRIPT),
  [ESLINT_PACKAGES.TYPESCRIPT_ESLINT]: async () => interopImport(
    import('typescript-eslint'),
  ),
  [ESLINT_PACKAGES.VITEST_ESLINT_PLUGIN]: async () => interopImport(
    import('@vitest/eslint-plugin'),
  ),
} satisfies Record<EslintPackage, (() => Promise<unknown>) | (() => boolean)>;

export const COMMITLINT_PACKAGES = <const>{
  COMMITLINT_CONFIG_CONVENTIONAL: '@commitlint/config-conventional',
  COMMITLINT_PLUGIN_FUNCTION_RULES: 'commitlint-plugin-function-rules',
  COMMITLINT_PLUGIN_TENSE: 'commitlint-plugin-tense',
};

export type CommitlintPackage = typeof COMMITLINT_PACKAGES[keyof typeof COMMITLINT_PACKAGES];

export const COMMITLINT_PACKAGE_RESOLVERS = <const>{
  [COMMITLINT_PACKAGES.COMMITLINT_CONFIG_CONVENTIONAL]: () => isPackageExists(
    COMMITLINT_PACKAGES.COMMITLINT_CONFIG_CONVENTIONAL,
  ),
  [COMMITLINT_PACKAGES.COMMITLINT_PLUGIN_FUNCTION_RULES]: () => isPackageExists(
    COMMITLINT_PACKAGES.COMMITLINT_PLUGIN_FUNCTION_RULES,
  ),
  [COMMITLINT_PACKAGES.COMMITLINT_PLUGIN_TENSE]: () => isPackageExists(
    COMMITLINT_PACKAGES.COMMITLINT_PLUGIN_TENSE,
  ),
} satisfies Record<CommitlintPackage, () => boolean>;

export const MARKDOWNLINT_PACKAGES = <const>{
  MARKDOWNLINT_GITHUB: '@github/markdownlint-github',
  MARKDOWNLINT_RULES: '@hongminhee/markdownlint-rules',
  MARKDOWNLINT_RULE_NO_TRAILING_SLASH_IN_LINKS: 'markdownlint-rule-no-trailing-slash-in-links',
  MARKDOWNLINT_RULE_RELATIVE_LINKS: 'markdownlint-rule-relative-links',
  MARKDOWNLINT_RULE_SEARCH_REPLACE: 'markdownlint-rule-search-replace',
  MARKDOWNLINT_RULE_TABLE_FORMAT: 'markdownlint-rule-table-format',
};

export type MarkdownlintPackage = typeof MARKDOWNLINT_PACKAGES[keyof typeof MARKDOWNLINT_PACKAGES];

export const MARKDOWNLINT_PACKAGE_RESOLVERS = <const>{
  [MARKDOWNLINT_PACKAGES.MARKDOWNLINT_GITHUB]: () => isPackageExists(
    MARKDOWNLINT_PACKAGES.MARKDOWNLINT_GITHUB,
  ),
  [MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULES]: () => isPackageExists(
    MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULES,
  ),
  [MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_NO_TRAILING_SLASH_IN_LINKS]: () => isPackageExists(
    MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_NO_TRAILING_SLASH_IN_LINKS,
  ),
  [MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_RELATIVE_LINKS]: () => isPackageExists(
    MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_RELATIVE_LINKS,
  ),
  [MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_SEARCH_REPLACE]: () => isPackageExists(
    MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_SEARCH_REPLACE,
  ),
  [MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_TABLE_FORMAT]: () => isPackageExists(
    MARKDOWNLINT_PACKAGES.MARKDOWNLINT_RULE_TABLE_FORMAT,
  ),
} satisfies Record<MarkdownlintPackage, () => boolean>;

export const STYLELINT_PACKAGES = <const>{
  POSTCSS_HTML: 'postcss-html',
  STYLELINT_CONFIG_CSS_MODULES: 'stylelint-config-css-modules',
  STYLELINT_CONFIG_HTML: 'stylelint-config-html',
  STYLELINT_CONFIG_RECESS_ORDER: 'stylelint-config-recess-order',
  STYLELINT_CONFIG_STANDARD_LESS: 'stylelint-config-standard-less',
  STYLELINT_CONFIG_STANDARD_SCSS: 'stylelint-config-standard-scss',
  STYLELINT_DECLARATION_STRICT_VALUE: 'stylelint-declaration-strict-value',
  STYLELINT_ORDER: 'stylelint-order',
  STYLELINT_PLUGIN_DEFENSIVE_CSS: 'stylelint-plugin-defensive-css',
  STYLELINT_PLUGIN_USE_BASELINE: 'stylelint-plugin-use-baseline',
  STYLELINT_USE_NESTING: 'stylelint-use-nesting',
  STYLISTIC_STYLELINT_CONFIG: '@stylistic/stylelint-config',
};

export type StylelintPackage = typeof STYLELINT_PACKAGES[keyof typeof STYLELINT_PACKAGES];

export const STYLELINT_PACKAGE_RESOLVERS = <const>{
  [STYLELINT_PACKAGES.POSTCSS_HTML]: () => isPackageExists(
    STYLELINT_PACKAGES.POSTCSS_HTML,
  ),
  [STYLELINT_PACKAGES.STYLELINT_CONFIG_CSS_MODULES]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_CONFIG_CSS_MODULES,
  ),
  [STYLELINT_PACKAGES.STYLELINT_CONFIG_HTML]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_CONFIG_HTML,
  ),
  [STYLELINT_PACKAGES.STYLELINT_CONFIG_RECESS_ORDER]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_CONFIG_RECESS_ORDER,
  ),
  [STYLELINT_PACKAGES.STYLELINT_CONFIG_STANDARD_LESS]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_CONFIG_STANDARD_LESS,
  ),
  [STYLELINT_PACKAGES.STYLELINT_CONFIG_STANDARD_SCSS]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_CONFIG_STANDARD_SCSS,
  ),
  [STYLELINT_PACKAGES.STYLELINT_DECLARATION_STRICT_VALUE]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_DECLARATION_STRICT_VALUE,
  ),
  [STYLELINT_PACKAGES.STYLELINT_ORDER]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_ORDER,
  ),
  [STYLELINT_PACKAGES.STYLELINT_PLUGIN_DEFENSIVE_CSS]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_PLUGIN_DEFENSIVE_CSS,
  ),
  [STYLELINT_PACKAGES.STYLELINT_PLUGIN_USE_BASELINE]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_PLUGIN_USE_BASELINE,
  ),
  [STYLELINT_PACKAGES.STYLELINT_USE_NESTING]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLELINT_USE_NESTING,
  ),
  [STYLELINT_PACKAGES.STYLISTIC_STYLELINT_CONFIG]: () => isPackageExists(
    STYLELINT_PACKAGES.STYLISTIC_STYLELINT_CONFIG,
  ),
} satisfies Record<StylelintPackage, () => boolean>;

export const VITEST_PACKAGES = <const>{
  HAPPY_DOM: 'happy-dom',
  JSDOM: 'jsdom',
  VITEST_UI: '@vitest/ui',
};

export type VitestPackage = typeof VITEST_PACKAGES[keyof typeof VITEST_PACKAGES];

export const VITEST_PACKAGE_RESOLVERS = <const>{
  [VITEST_PACKAGES.HAPPY_DOM]: () => isPackageExists(VITEST_PACKAGES.HAPPY_DOM),
  [VITEST_PACKAGES.JSDOM]: () => isPackageExists(VITEST_PACKAGES.JSDOM),
  [VITEST_PACKAGES.VITEST_UI]: () => isPackageExists(VITEST_PACKAGES.VITEST_UI),
} satisfies Record<VitestPackage, () => boolean>;
