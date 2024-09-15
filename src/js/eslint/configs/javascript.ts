import jsEslint from '@eslint/js';
import confusingBrowserGlobals from 'confusing-browser-globals';
import globals from 'globals';

import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_SCRIPT_FILES } from '../utils/globs';
import { isModuleEnabled, MODULES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const javascript = async (): Promise<Config[]> => {
  const {
    optional: [pluginAntfu, pluginUnusedImports],
  } = await resolvePackages(MODULES.javascript);

  const plugins: Config['plugins'] = {};
  const pluginRules: Config['rules'] = {};

  if (pluginAntfu) {
    plugins['antfu'] = pluginAntfu;
    pluginRules['antfu/consistent-chaining'] = 'error';
    pluginRules['antfu/no-ts-export-equal'] = 'error';
  }

  if (pluginUnusedImports) {
    plugins['unused'] = pluginUnusedImports;
    pluginRules['unused/no-unused-imports'] = 'error';
    pluginRules['unused/no-unused-vars'] = isModuleEnabled(MODULES.typescript) ? 'off' : 'error';
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.JAVASCRIPT, SUB_SCOPES.SETUP),
      plugins,
      languageOptions: {
        ecmaVersion: 'latest',
        globals: {
          ...globals.browser,
          ...globals.es2025,
          ...globals.node,
        },
        sourceType: 'module',
        parserOptions: {
          sourceType: 'module',
          ecmaVersion: 'latest',
          ecmaFeatures: {
            jsx: true,
          },
        },
      },
      linterOptions: {
        reportUnusedDisableDirectives: 'error',
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.JAVASCRIPT, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        ...jsEslint.configs.recommended.rules,
        ...(pluginUnusedImports
          ? {
            'no-unused-vars': 'off',
          }
          : undefined),
        'accessor-pairs': 'error',
        'array-callback-return': ['error', {
          checkForEach: true,
        }],
        'arrow-body-style': ['error', 'as-needed'],
        'block-scoped-var': 'error',
        'capitalized-comments': ['error', 'always', {
          block: {
            ignoreInlineComments: true,
          },
          line: {
            ignorePattern: '.*',
          },
        }],
        'class-methods-use-this': ['error', {
          ignoreOverrideMethods: true,
        }],
        complexity: ['error', {
          max: 10,
          variant: 'modified',
        }],
        'consistent-return': 'error',
        'consistent-this': 'error',
        curly: 'error',
        'default-case': 'error',
        'default-case-last': 'error',
        'default-param-last': 'error',
        'dot-notation': 'error',
        eqeqeq: 'error',
        'func-name-matching': ['error', {
          considerPropertyDescriptor: true,
          // eslint-disable-next-line ts/naming-convention -- Option needs to be cased like this
          includeCommonJSModuleExports: true,
        }],
        'func-names': 'error',
        'func-style': ['error', 'expression'],
        'grouped-accessor-pairs': 'error',
        'guard-for-in': 'error',
        'init-declarations': 'error',
        'logical-assignment-operators': 'error',
        'max-classes-per-file': 'error',
        'max-depth': 'error',
        'max-lines': ['error', {
          max: 300,
          skipBlankLines: true,
          skipComments: true,
        }],
        'max-lines-per-function': ['error', {
          // eslint-disable-next-line ts/naming-convention -- Option needs to be cased like this
          IIFEs: true,
          max: 50,
          skipBlankLines: true,
          skipComments: true,
        }],
        'max-nested-callbacks': ['error', {
          max: 3,
        }],
        'max-params': ['error', {
          max: 4,
        }],
        'max-statements': ['error', {
          max: 30,
        }],
        'new-cap': 'error',
        'no-alert': 'error',
        'no-array-constructor': 'error',
        'no-await-in-loop': 'error',
        'no-bitwise': 'error',
        'no-caller': 'error',
        'no-cond-assign': ['error', 'always'],
        'no-console': 'error',
        'no-constructor-return': 'error',
        'no-div-regex': 'error',
        'no-duplicate-imports': isModuleEnabled(MODULES.import)
          ? 'off'
          : ['error', {
            allowSeparateTypeImports: true,
          }],
        'no-else-return': ['error', {
          allowElseIf: false,
        }],
        'no-empty-function': 'error',
        'no-eq-null': 'error',
        'no-eval': 'error',
        'no-extend-native': 'error',
        'no-extra-bind': 'error',
        'no-extra-label': 'error',
        'no-implicit-coercion': ['error', {
          boolean: false,
        }],
        'no-implicit-globals': 'error',
        'no-implied-eval': 'error',
        'no-inline-comments': 'error',
        'no-inner-declarations': 'error',
        'no-invalid-this': 'error',
        'no-iterator': 'error',
        'no-label-var': 'error',
        'no-labels': 'error',
        'no-lone-blocks': 'error',
        'no-lonely-if': 'error',
        'no-loop-func': 'error',
        'no-magic-numbers': ['error', {
          ignore: [
            -1,
            0,
            1,
            100,
            42_069,
          ],
        }],
        'no-multi-assign': 'error',
        'no-multi-str': 'error',
        'no-negated-condition': 'error',
        'no-nested-ternary': 'error',
        'no-new': 'error',
        'no-new-func': 'error',
        'no-new-wrappers': 'error',
        'no-object-constructor': 'error',
        'no-octal-escape': 'error',
        'no-param-reassign': ['error', {
          props: false,
        }],
        'no-plusplus': 'error',
        'no-promise-executor-return': 'error',
        'no-proto': 'error',
        'no-restricted-exports': ['error', {
          restrictedNamedExports: ['default'],
        }],
        'no-restricted-globals': ['error', 'global', ...confusingBrowserGlobals],
        'no-restricted-properties': [
          'error',
          { message: 'Use `Object.getPrototypeOf` or `Object.setPrototypeOf` instead.', property: '__proto__' },
          { message: 'Use the `get` syntax or `Object.defineProperty` instead.', property: '__defineGetter__' },
          { message: 'Use the `set` syntax or `Object.defineProperty` instead.', property: '__defineSetter__' },
          { message: 'Use `Object.getOwnPropertyDescriptor` instead.', property: '__lookupGetter__' },
          { message: 'Use `Object.getOwnPropertyDescriptor` instead.', property: '__lookupSetter__' },
        ],
        'no-restricted-syntax': [
          'error',
          {
            selector: 'ForInStatement',
            message: '`for..in` loops include inherited properties, leading to unexpected behavior.'
              + ' Use Object.{keys, values, entries} for safe iteration'
              + ' or explicitly disable this rule to make the intent clear.',
          },
          {
            selector: 'TSEnumDeclaration',
            message: 'TypeScript enums can lead to less predictable type behavior.'
              + ' Use a constant object literal (`const MY_ENUM = <const>{ VALUE1: 1, VALUE2: 2 };`)'
              + ' and add a type for it (`type MyEnum = typeof MY_ENUM[keyof typeof MY_ENUM];`)'
              + ' instead to ensure stricter typing control'
              + ' and better code clarity.',
          },
          {
            selector: 'TSExportAssignment',
            message: 'TypeScript export assignments should be avoided for consistency and maintainability.'
              + ' Use a default export instead to align with modern module standards'
              + ' and to avoid potential import/export issues.',
          },
        ],
        'no-return-assign': ['error', 'always'],
        'no-script-url': 'error',
        'no-self-compare': 'error',
        'no-sequences': 'error',
        'no-shadow': 'error',
        'no-template-curly-in-string': 'error',
        'no-throw-literal': 'error',
        'no-unassigned-vars': 'error',
        'no-underscore-dangle': 'error',
        'no-unmodified-loop-condition': 'error',
        'no-unneeded-ternary': ['error', {
          defaultAssignment: false,
        }],
        'no-unreachable-loop': 'error',
        'no-unused-expressions': 'error',
        'no-use-before-define': 'error',
        'no-useless-assignment': 'error',
        'no-useless-call': 'error',
        'no-useless-computed-key': 'error',
        'no-useless-concat': 'error',
        'no-useless-constructor': 'error',
        'no-useless-rename': 'error',
        'no-useless-return': 'error',
        'no-var': 'error',
        'no-void': 'error',
        'no-warning-comments': 'error',
        'object-shorthand': ['error', 'always', {
          avoidQuotes: true,
          ignoreConstructors: false,
        }],
        'operator-assignment': 'error',
        'prefer-arrow-callback': 'error',
        'prefer-const': ['error', {
          ignoreReadBeforeAssign: true,
        }],
        'prefer-destructuring': 'error',
        'prefer-exponentiation-operator': 'error',
        'prefer-named-capture-group': 'error',
        'prefer-numeric-literals': 'error',
        'prefer-object-has-own': 'error',
        'prefer-object-spread': 'error',
        'prefer-promise-reject-errors': 'error',
        'prefer-regex-literals': ['error', {
          disallowRedundantWrapping: true,
        }],
        'prefer-rest-params': 'error',
        'prefer-spread': 'error',
        'prefer-template': 'error',
        'preserve-caught-error': 'error',
        radix: 'error',
        'require-atomic-updates': 'error',
        'require-await': 'error',
        strict: 'error',
        'symbol-description': 'error',
        'unicode-bom': 'error',
        'use-isnan': ['error', {
          enforceForIndexOf: true,
        }],
        'valid-typeof': ['error', {
          requireStringLiterals: true,
        }],
        'vars-on-top': 'error',
        yoda: 'error',
        ...pluginRules,
      },
    },
  ];
};
