/**
 * @internal @brnshkr/config/eslint
 */

import { REQUIRED_ATTRIBUTES, RESTRICTED_ATTRIBUTE_VALUES, RESTRICTED_ATTRIBUTES } from '#eslint/configs/html.ts';
import { getTsEslintParserIfExists } from '#eslint/configs/typescript.ts';
import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { ATTRIBUTE_GROUP_PREFIXES, ATTRIBUTE_ORDER, TRAILING_ATTRIBUTES } from '#eslint/utils/attributes.ts';
import { buildConfigName, renameRules } from '#eslint/utils/config.ts';
import { GLOB_SVELTE, GLOB_SVELTE_SCRIPT } from '#eslint/utils/globs.ts';
import { MODULES, resolvePackages } from '#eslint/utils/module.ts';
import { INDENT } from '#shared/utils/constants.ts';

import type { Config } from '#eslint/types/config.ts';

const ATTRIBUTE_DIRECTIVE_GROUPS = [
  <[string, ...string[]]>[
    '/^bind:/u',
    '!bind:this',
    '/^on:/u',
  ],
  ...[
    'use',
    'transition',
    'in',
    'out',
    'animate',
    'let',
  ].map((directive) => ({
    match: `/^${directive}:/u`,
    sort: <const>'alphabetical',
  })),
];

const ORDERED_ATTRIBUTE_NAMES = [
  'this',
  'slot',
  ...ATTRIBUTE_ORDER,
  ...TRAILING_ATTRIBUTES,
].toSorted((first, second) => first.localeCompare(second));

const SVELTE_ATTRIBUTE_ORDER = [
  'this',
  'bind:this',
  ...ATTRIBUTE_ORDER.filter((attribute) => attribute !== 'class' && attribute !== 'style'),
  'class',
  {
    match: '/^class:/u',
    sort: <const>'alphabetical',
  },
  'style',
  {
    match: '/^style:/u',
    sort: <const>'alphabetical',
  },
  'slot',
  {
    match: '/^--/u',
    sort: <const>'alphabetical',
  },
  {
    match: <[string, ...string[]]>[
      '!/:/u',
      '!/^--/u',
      `!/^(?:${ORDERED_ATTRIBUTE_NAMES.join('|')})$/u`,
      ...ATTRIBUTE_GROUP_PREFIXES.map((prefix) => `!/^${prefix}/u`),
    ],
    sort: <const>'alphabetical',
  },
  ...TRAILING_ATTRIBUTES,
  ...ATTRIBUTE_GROUP_PREFIXES.map((prefix) => ({
    match: `/^${prefix}/u`,
    sort: <const>'alphabetical',
  })),
  ...ATTRIBUTE_DIRECTIVE_GROUPS,
];

/**
 * @throws {Error}
 */
const extractRelevantConfig = (configs: Config[], key: string): Config => {
  for (const config of configs) {
    if (config.name === `svelte:${key}` && config.rules) {
      return config;
    }
  }

  throw new Error(`Expected key "${key}" to be contained in given config.`);
};

/**
 * @rejects {Error}
 */
export const svelte = async (): Promise<Config[]> => {
  const {
    requiredAll: [isSvelteInstalled, pluginSvelte],
    optional: [pluginHtmlSvelte],
  } = await resolvePackages(MODULES.svelte);

  if (!isSvelteInstalled || !pluginSvelte) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.SVELTE, SUB_SCOPES.SETUP),
      plugins: {
        svelte: pluginSvelte,
        ...(pluginHtmlSvelte
          ? {
            'svelte-html': pluginHtmlSvelte,
          }
          : undefined),
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.SVELTE, SUB_SCOPES.PARSER),
      files: [GLOB_SVELTE, GLOB_SVELTE_SCRIPT],
      languageOptions: {
        parser: extractRelevantConfig(pluginSvelte.configs.base, 'base:setup-for-svelte').languageOptions?.['parser'],
        parserOptions: {
          extraFileExtensions: ['.svelte'],
          parser: await getTsEslintParserIfExists(),
        },
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.SVELTE, SUB_SCOPES.PROCESSOR),
      files: [GLOB_SVELTE],
      processor: pluginSvelte.processors.svelte,
    },
    {
      name: buildConfigName(MAIN_SCOPES.SVELTE, SUB_SCOPES.RULES),
      files: [GLOB_SVELTE, GLOB_SVELTE_SCRIPT],
      rules: {
        ...extractRelevantConfig(pluginSvelte.configs.recommended, 'recommended:rules').rules,
        'svelte/block-lang': ['error', {
          enforceScriptPresent: false,
          enforceStylePresent: false,
          /* eslint-disable unicorn/no-null -- Null is required here to allow for tags without a lang attribute */
          script: ['ts', null],
          style: ['scss', null],
          /* eslint-enable unicorn/no-null */
        }],
        'svelte/button-has-type': 'error',
        'svelte/consistent-selector-style': ['error', {
          style: ['type', 'class', 'id'],
        }],
        'svelte/derived-has-same-inputs-outputs': 'error',
        'svelte/experimental-require-strict-events': 'error',
        'svelte/experimental-require-slot-types': 'error',
        'svelte/first-attribute-linebreak': 'error',
        'svelte/html-closing-bracket-new-line': 'error',
        'svelte/html-closing-bracket-spacing': ['error', {
          selfClosingTag: 'never',
        }],
        'svelte/html-quotes': 'error',
        'svelte/html-self-closing': ['error', {
          normal: 'ignore',
          void: 'never',
        }],
        'style/indent': 'off',
        'svelte/indent': ['error', {
          indent: INDENT,
        }],
        'svelte/max-attributes-per-line': ['error', {
          singleline: 3,
        }],
        'svelte/mustache-spacing': 'error',
        'svelte/no-add-event-listener': 'error',
        'svelte/no-at-const-tags': 'error',
        'svelte/no-at-debug-tags': 'error',
        'svelte/no-bind-value-on-checkable-inputs': 'error',
        'svelte/no-conflicting-module-names': 'error',
        'svelte/no-extra-reactive-curlies': 'error',
        'svelte/no-ignored-unsubscribe': 'error',
        'svelte/no-inline-styles': ['error', {
          allowTransitions: false,
        }],
        'svelte/no-inspect': 'error',
        'svelte/no-nested-style-tag': 'error',
        'svelte/no-spaces-around-equal-signs-in-attribute': 'error',
        'svelte/no-target-blank': 'error',
        'svelte/no-top-level-browser-globals': 'error',
        'style/no-trailing-spaces': 'off',
        'svelte/no-trailing-spaces': 'error',
        'svelte/no-unused-props': ['error', {
          checkImportedTypes: true,
        }],
        'svelte/prefer-attribute-interpolation': 'error',
        'svelte/prefer-class-directive': 'error',
        'prefer-const': 'off',
        'svelte/prefer-const': 'error',
        'svelte/prefer-derived-over-derived-by': 'error',
        'svelte/prefer-destructured-store-props': 'error',
        'svelte/prefer-style-directive': 'error',
        'svelte/require-event-prefix': 'error',
        'svelte/require-optimized-style-attribute': 'error',
        'svelte/require-store-callbacks-use-set-param': 'error',
        'svelte/require-stores-init': 'error',
        'svelte/shorthand-attribute': 'error',
        'svelte/shorthand-directive': 'error',
        'svelte/sort-attributes': ['error', {
          order: SVELTE_ATTRIBUTE_ORDER,
        }],
        'svelte/spaced-html-comment': 'error',
        'svelte/valid-compile': 'error',
        'svelte/valid-style-parse': 'error',
      },
    },
    ...(pluginHtmlSvelte
      ? [
        {
          name: buildConfigName(MAIN_SCOPES.SVELTE, `${SUB_SCOPES.RULES}-html`),
          files: [GLOB_SVELTE],
          rules: {
            ...renameRules(pluginHtmlSvelte.configs.recommended.rules, { '@html-eslint/svelte': 'svelte-html' }),
            'svelte-html/no-restricted-attr-values': [
              'error',
              ...RESTRICTED_ATTRIBUTE_VALUES,
            ],
            'svelte-html/no-restricted-attrs': [
              'error',
              ...RESTRICTED_ATTRIBUTES,
            ],
            'svelte-html/require-attrs': [
              'error',
              ...REQUIRED_ATTRIBUTES,
            ],
            'svelte-html/use-baseline': 'error',
          },
        } satisfies Config,
      ]
      : []),
  ];
};
