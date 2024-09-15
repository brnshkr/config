import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';

import {
  GLOB_CJS,
  GLOB_DEVELOPMENT_FILES,
  GLOB_DTS,
  GLOB_EXAMPLES,
  GLOB_SCRIPT_FILES,
  GLOB_SVELTE,
  GLOB_TEST_FILES,
  GLOB_TOML,
  GLOB_TS,
  GLOB_YAML,
} from '../utils/globs';

import { isModuleEnabled, MODULES } from '../utils/module';

import { FILE_NAMES_TO_IGNORE } from './unicorn';

import type { Config } from '../types/config';

const jsOverrides: Config[] = [
  {
    name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.JAVASCRIPT}/scripts`),
    files: GLOB_SCRIPT_FILES.map((glob) => `scripts/${glob}`),
    languageOptions: {
      sourceType: 'script',
      parserOptions: {
        sourceType: 'script',
      },
    },
    rules: {
      'no-console': 'off',
      strict: ['error', 'global'],
    },
  },
  {
    name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.JAVASCRIPT}/cjs`),
    files: [GLOB_CJS],
    languageOptions: {
      sourceType: 'commonjs',
      parserOptions: {
        sourceType: 'commonjs',
      },
    },
    rules: {
      strict: ['error', 'global'],
      'unicorn/prefer-module': 'off',
      'ts/no-require-imports': 'off',
    },
  },
];

const tsOverrides: Config[] = isModuleEnabled(MODULES.typescript)
  ? [
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.TYPESCRIPT}/ts`),
      files: [GLOB_TS],
      rules: {
        strict: 'off',
        'node/no-missing-import': 'off',
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.TYPESCRIPT}/dts`),
      files: [GLOB_DTS],
      rules: {
        'max-lines': 'off',
        'no-restricted-syntax': 'off',
        'no-var': 'off',
        'vars-on-top': 'off',
        'comments/no-unlimited-disable': 'off',
        'import/no-extraneous-dependencies': 'off',
        'import/unambiguous': 'off',
      },
    },
  ]
  : [];

const testOverrides: Config[] = isModuleEnabled(MODULES.test)
  ? [
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.TEST}/general`),
      files: GLOB_TEST_FILES,
      rules: {
        'node/no-sync': 'off',
      },
    },
  ]
  : [];

const unicornOverrides: Config[] = isModuleEnabled(MODULES.unicorn)
  ? [
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.UNICORN}/general`),
      files: [
        ...GLOB_SCRIPT_FILES.map((glob) => `**/classes/${glob}`),
        ...GLOB_SCRIPT_FILES.map((glob) => `**/errors/${glob}`),
      ],
      rules: {
        'unicorn/filename-case': ['error', {
          case: 'pascalCase',
          ignore: FILE_NAMES_TO_IGNORE,
        }],
      },
    },
  ]
  : [];

const jsdocOverrides: Config[] = isModuleEnabled(MODULES.jsdoc)
  ? [
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.JSDOC}/${SUB_SCOPES.EXAMPLES}`),
      files: [GLOB_EXAMPLES],
      rules: {
        'no-console': 'off',
        'no-undef': 'off',
        'no-unused-expressions': 'off',
        'no-unused-vars': 'off',
        'node/no-missing-import': 'off',
        'node/no-missing-require': 'off',
        strict: 'off',
        'import/no-unresolved': 'off',
        'import/unambiguous': 'off',
        'style/eol-last': 'off',
        'style/no-multiple-empty-lines': 'off',
        'ts/no-unused-expressions': 'off',
        'ts/no-unused-vars': 'off',
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.JSDOC}/default-expressions`),
      files: ['**/*.jsdoc-defaults', '**/*.jsdoc-params', '**/*.jsdoc-properties'],
      rules: {
        'no-empty-function': 'off',
        'no-new': 'off',
        strict: 'off',
        'import/unambiguous': 'off',
        'style/eol-last': 'off',
        'style/no-extra-parens': 'off',
        'style/semi': 'off',
      },
    },
  ]
  : [];

const svelteOverrides: Config[] = isModuleEnabled(MODULES.svelte)
  ? [
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.SVELTE}/general`),
      files: [GLOB_SVELTE],
      rules: {
        'no-inner-declarations': 'off',
        'no-self-assign': 'off',
        'import/no-rename-default': 'off',
        'import/unambiguous': 'off',
        'svelte/comment-directive': ['error', {
          reportUnusedDisableDirectives: true,
        }],
        'svelte/system': 'error',
      },
    }, {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.SVELTE}/components`),
      files: [
        `**/components/${GLOB_SVELTE}`,
      ],
      rules: {
        'unicorn/filename-case': ['error', {
          case: 'pascalCase',
        }],
      },
    },
  ]
  : [];

const tomlOverrides: Config[] = isModuleEnabled(MODULES.toml)
  ? [
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.TOML}/general`),
      files: [GLOB_TOML],
      rules: {
        'no-irregular-whitespace': 'off',
      },
    },
  ]
  : [];

const yamlOverrides: Config[] = isModuleEnabled(MODULES.yaml)
  ? [
    {
      name: buildConfigName(MAIN_SCOPES.OVERRIDES, `${MAIN_SCOPES.YAML}/general`),
      files: [GLOB_YAML],
      rules: {
        'no-irregular-whitespace': 'off',
        'no-unused-vars': 'off',
      },
    },
  ]
  : [];

export const overrides = (): Config[] => [
  ...jsOverrides,
  ...tsOverrides,
  ...testOverrides,
  ...unicornOverrides,
  ...jsdocOverrides,
  ...svelteOverrides,
  ...tomlOverrides,
  ...yamlOverrides,
  {
    name: buildConfigName(MAIN_SCOPES.OVERRIDES, SUB_SCOPES.DEVELOPMENT),
    files: GLOB_DEVELOPMENT_FILES,
    rules: {
      'max-lines': 'off',
      'max-lines-per-function': 'off',
      'no-irregular-whitespace': 'off',
      'no-restricted-exports': 'off',
      'import/max-dependencies': 'off',
      'import/no-default-export': 'off',
      'import/no-rename-default': 'off',
      'import/no-named-as-default-member': 'off',
      'node/no-sync': 'off',
    },
  },
];
