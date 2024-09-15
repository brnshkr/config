import { INDENT } from '../../shared/utils/constants';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_SVELTE, GLOB_SVELTE_SCRIPT } from '../utils/globs';
import { MODULES, resolvePackages } from '../utils/module';

import { getTsEslintParserIfExists } from './typescript';

import type { Config } from '../types/config';

const extractRelevantConfig = (configs: Config[], key: string): Config => {
  for (const config of configs) {
    if (config.name === `svelte:${key}` && config.rules) {
      return config;
    }
  }

  throw new Error(`Expected key "${key}" to be contained in given config.`);
};

export const svelte = async (): Promise<Config[]> => {
  const {
    requiredAll: [isSvelteInstalled, pluginSvelte],
  } = await resolvePackages(MODULES.svelte);

  if (!isSvelteInstalled || !pluginSvelte) {
    return [];
  }

  return [
    {
      name: buildConfigName(MAIN_SCOPES.SVELTE, SUB_SCOPES.SETUP),
      plugins: {
        svelte: pluginSvelte,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.SVELTE, SUB_SCOPES.PARSER),
      files: [GLOB_SVELTE, GLOB_SVELTE_SCRIPT],
      languageOptions: {
        parser: extractRelevantConfig(pluginSvelte.configs['flat/base'], 'base:setup-for-svelte').languageOptions?.['parser'],
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
        ...extractRelevantConfig(pluginSvelte.configs['flat/recommended'], 'recommended:rules').rules,
        'svelte/block-lang': ['error', {
          enforceScriptPresent: false,
          enforceStylePresent: false,
          /* eslint-disable unicorn/no-null -- Null is required here to allow for tags without a lang attribute */
          script: ['ts', null],
          style: ['scss', null],
          /* eslint-enable unicorn/no-null -- Restore rule */
        }],
        'svelte/button-has-type': 'error',
        'svelte/consistent-selector-style': ['error', {
          checkGlobal: false,
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
          multiline: 1,
          singleline: 3,
        }],
        'svelte/mustache-spacing': 'error',
        'svelte/no-add-event-listener': 'error',
        'svelte/no-at-debug-tags': 'error',
        'svelte/no-extra-reactive-curlies': 'error',
        'svelte/no-ignored-unsubscribe': 'error',
        'svelte/no-inline-styles': 'error',
        'svelte/no-inspect': 'error',
        'svelte/no-spaces-around-equal-signs-in-attribute': 'error',
        'svelte/no-target-blank': 'error',
        'svelte/no-top-level-browser-globals': 'error',
        'style/no-trailing-spaces': 'off',
        'svelte/no-trailing-spaces': 'error',
        'svelte/prefer-class-directive': 'error',
        'prefer-const': 'off',
        'svelte/prefer-const': 'error',
        'svelte/prefer-destructured-store-props': 'error',
        'svelte/prefer-style-directive': 'error',
        'svelte/require-event-prefix': 'error',
        'svelte/require-optimized-style-attribute': 'error',
        'svelte/require-store-callbacks-use-set-param': 'error',
        'svelte/require-stores-init': 'error',
        'svelte/shorthand-attribute': 'error',
        'svelte/shorthand-directive': 'error',
        'svelte/sort-attributes': 'error',
        'svelte/spaced-html-comment': 'error',
        'svelte/valid-compile': 'error',
        'svelte/valid-style-parse': 'error',
      },
    },
  ];
};
