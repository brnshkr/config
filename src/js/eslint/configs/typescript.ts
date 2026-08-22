import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName, renameRules } from '../utils/config';
import { GLOB_MD, GLOB_SCRIPT_FILES, GLOB_TS } from '../utils/globs';
import { isModuleEnabled, MODULES, resolvePackages } from '../utils/module';
import { doesTsConfigExist, resolveTsConfigPath } from '../utils/tsconfig';

import type { ESLint } from 'eslint';
import type { Maybe } from '../../shared/types/core';
import type { Config, TsEslintConfigArray, TsEslintParser } from '../types/config';
import type { TypeAwareOptions, TypescriptOptions } from '../types/options';

export const DEFAULT_TYPE_AWARE_IGNORES = [
  `${GLOB_MD}/**`,
];

export const getTsEslintParserIfExists = async (): Promise<Maybe<TsEslintParser>> => {
  const isTypescriptModuleEnabled = isModuleEnabled(MODULES.typescript);
  let parser = undefined;

  if (isTypescriptModuleEnabled) {
    const {
      requiredAll: [, tsEslint],
    } = await resolvePackages(MODULES.typescript);

    if (tsEslint) {
      ({ parser } = tsEslint);
    }
  }

  return parser;
};

const resolveTypeAwareOptions = (
  resolvedOptions: TypescriptOptions,
  files: NonNullable<TypeAwareOptions['files']>,
  ignoredGlobs: NonNullable<TypeAwareOptions['ignores']>,
): TypeAwareOptions => {
  const typeAwareOptions: TypeAwareOptions = typeof resolvedOptions.typeAware === 'object'
    ? resolvedOptions.typeAware
    : {
      ignores: DEFAULT_TYPE_AWARE_IGNORES,
      tsconfig: typeof resolvedOptions.typeAware === 'string'
        ? resolvedOptions.typeAware
        : undefined,
    };

  typeAwareOptions.files = [...new Set([...(typeAwareOptions.files ?? []), ...files])];
  typeAwareOptions.ignores = [...new Set([...(typeAwareOptions.ignores ?? []), ...ignoredGlobs])];

  return typeAwareOptions;
};

const extractRelevantRules = (configs: TsEslintConfigArray, key: string): NonNullable<Config['rules']> => {
  for (const config of configs) {
    if (config.name === `typescript-eslint/${key}` && config.rules) {
      return renameRules(config.rules, { '@typescript-eslint': 'ts' });
    }
  }

  throw new Error(`Expected key "${key}" to be contained in given config.`);
};

const getNamingConvention = (): NonNullable<Config['rules']>['ts/naming-convention'] => [
  'error',
  {
    selector: 'default',
    format: ['strictCamelCase', 'StrictPascalCase', 'UPPER_CASE'],
    leadingUnderscore: 'forbid',
    trailingUnderscore: 'forbid',
  },
  {
    selector: ['objectLiteralProperty', 'variable'],
    format: ['strictCamelCase', 'UPPER_CASE'],
  },
  {
    selector: 'variable',
    // eslint-disable-next-line unicorn/no-null -- Null is required here
    format: null,
    modifiers: ['destructured'],
  },
  {
    selector: 'typeLike',
    format: ['StrictPascalCase'],
  },
  {
    selector: 'parameter',
    // eslint-disable-next-line unicorn/no-null -- Null is required here
    format: null,
    filter: {
      regex: '^_+$',
      match: false,
    },
  },
  {
    selector: [
      'classProperty',
      'objectLiteralProperty',
      'typeProperty',
      'classMethod',
      'objectLiteralMethod',
      'typeMethod',
      'accessor',
      'enumMember',
    ],
    // eslint-disable-next-line unicorn/no-null -- Null is required here
    format: null,
    modifiers: ['requiresQuotes'],
  },
  {
    selector: 'typeParameter',
    format: ['StrictPascalCase'],
    prefix: ['T'],
    custom: {
      regex: '^[A-Z]',
      match: true,
    },
  },
  {
    selector: 'interface',
    format: ['StrictPascalCase'],
    custom: {
      regex: '^I[A-Z]',
      match: false,
    },
  },
];

export const typescript = async (options?: Partial<TypescriptOptions>): Promise<Config[]> => {
  const {
    requiredAll: [isTypescriptInstalled, tsEslint],
  } = await resolvePackages(MODULES.typescript);

  if (!isTypescriptInstalled || !tsEslint) {
    return [];
  }

  const {
    optional: [, pluginUnusedImports],
  } = await resolvePackages(MODULES.javascript);

  const cwd = process.cwd();
  const hasTsConfig = doesTsConfigExist(resolveTsConfigPath(options));

  const resolvedOptions = <const>{
    extraFileExtensions: [],
    parserOptions: {},
    typeAware: hasTsConfig,
    ...options,
  } satisfies TypescriptOptions;

  const ignoredGlobs = resolvedOptions.ignores ?? [];

  const files = [...new Set([
    ...resolvedOptions.extraFileExtensions.map((extension) => `**/*.${extension}`),
    ...resolvedOptions.files ?? GLOB_SCRIPT_FILES,
  ])];

  const hasEnabledTypeAwareness = resolvedOptions.typeAware !== false;

  const typeAwareOptions = hasEnabledTypeAwareness
    ? resolveTypeAwareOptions(resolvedOptions, files, ignoredGlobs)
    : {};

  const createParserConfig = (isTypeAware: boolean): Config => ({
    name: buildConfigName(MAIN_SCOPES.TYPESCRIPT, `${SUB_SCOPES.PARSER}${isTypeAware ? '-type-aware' : ''}`),
    files: isTypeAware ? typeAwareOptions.files : files,
    ignores: isTypeAware ? typeAwareOptions.ignores : ignoredGlobs,
    languageOptions: {
      parser: tsEslint.parser,
      parserOptions: {
        extraFileExtensions: resolvedOptions.extraFileExtensions.map((extension) => `.${extension}`),
        ...(isTypeAware
          ? {
            tsconfigRootDir: cwd,
            projectService: {
              defaultProject: typeAwareOptions.tsconfig,
            },
          }
          : {}),
        ...resolvedOptions.parserOptions,
      },
    },
  });

  const createRulesConfig = (isTypeAware: boolean): Config => ({
    name: buildConfigName(MAIN_SCOPES.TYPESCRIPT, `${SUB_SCOPES.RULES}${isTypeAware ? '-type-aware' : ''}`),
    files: isTypeAware ? typeAwareOptions.files : files,
    ignores: isTypeAware ? typeAwareOptions.ignores : ignoredGlobs,
    rules: {
      'ts/naming-convention': getNamingConvention(),
      ...(isTypeAware
        ? {
          ...extractRelevantRules(tsEslint.configs.recommendedTypeCheckedOnly, 'recommended-type-checked-only'),
          ...extractRelevantRules(tsEslint.configs.strictTypeCheckedOnly, 'strict-type-checked-only'),
          ...extractRelevantRules(tsEslint.configs.stylisticTypeCheckedOnly, 'stylistic-type-checked-only'),
          'consistent-return': 'off',
          'ts/consistent-return': 'error',
          'ts/consistent-type-exports': 'error',
          'ts/no-unnecessary-qualifier': 'error',
          'prefer-destructuring': 'off',
          'ts/prefer-destructuring': 'error',
          'ts/no-unnecessary-type-conversion': 'error',
          'ts/promise-function-async': 'error',
          'ts/prefer-readonly': 'error',
          'ts/require-array-sort-compare': 'error',
          'ts/strict-boolean-expressions': 'error',
          'ts/strict-void-return': 'error',
          'ts/switch-exhaustiveness-check': ['error', {
            requireDefaultForNonUnion: true,
          }],
        }
        : {
          ...extractRelevantRules(tsEslint.configs.recommended, 'recommended'),
          ...extractRelevantRules(tsEslint.configs.strict, 'strict'),
          ...extractRelevantRules(tsEslint.configs.stylistic, 'stylistic'),
          ...(pluginUnusedImports
            ? {
              'ts/no-unused-vars': 'off',
            }
            : undefined),
          'ts/consistent-type-assertions': [
            'error',
            {
              assertionStyle: 'angle-bracket',
            },
          ],
          'ts/consistent-type-imports': 'error',
          'ts/member-ordering': 'error',
          'ts/method-signature-style': 'error',
          'ts/no-import-type-side-effects': 'error',
          'no-redeclare': 'off',
          'ts/no-redeclare': 'error',
          'ts/no-unnecessary-parameter-property-assignment': 'error',
          'no-unused-private-class-members': 'off',
          'ts/no-unused-private-class-members': 'error',
          'ts/no-useless-empty-export': 'error',
          'ts/prefer-enum-initializers': 'error',
        }),
    },
  });

  return [
    {
      name: buildConfigName(MAIN_SCOPES.TYPESCRIPT, SUB_SCOPES.SETUP),
      plugins: {
        ts: <ESLint.Plugin>tsEslint.plugin,
      },
    },
    createParserConfig(false),
    hasEnabledTypeAwareness ? createParserConfig(true) : undefined,
    createRulesConfig(false),
    hasEnabledTypeAwareness ? createRulesConfig(true) : undefined,
    {
      name: buildConfigName(MAIN_SCOPES.TYPESCRIPT, `${SUB_SCOPES.RULES}-typescript`),
      files: [GLOB_TS],
      ignores: typeAwareOptions.ignores ?? ignoredGlobs,
      rules: {
        'ts/explicit-function-return-type': 'error',
        'ts/explicit-member-accessibility': 'error',
      },
    },
  ].filter(Boolean);
};
