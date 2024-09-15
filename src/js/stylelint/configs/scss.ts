import { OVERRIDES } from '../types/overrides';
import { buildOverrideName } from '../utils/config';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const scss = (): Config[] => {
  const {
    requiredAll: [isStylelintConfigStandardScssInstalled],
  } = resolvePackages(MODULES.scss);

  if (!isStylelintConfigStandardScssInstalled) {
    return [];
  }

  return [
    {
      extends: PACKAGES.STYLELINT_CONFIG_STANDARD_SCSS,
      rules: {
        'scss/at-each-key-value-single-line': true,
        'scss/at-mixin-argumentless-call-parentheses': 'always',
        'scss/at-mixin-no-risky-nesting-selector': true,
        'scss/at-root-no-redundant': true,
        'scss/at-use-no-redundant-alias': true,
        'scss/block-no-redundant-nesting': true,
        'scss/declaration-nested-properties': 'never',
        'scss/dimension-no-non-numeric-values': true,
        'scss/dollar-variable-first-in-block': [true, {
          ignore: ['comments', 'imports'],
          except: ['function', 'mixin', 'if-else', 'loops'],
        }],
        'scss/dollar-variable-no-namespaced-assignment': true,
        'scss/double-slash-comment-inline': 'never',
        'scss/function-color-channel': true,
        'scss/function-color-relative': true,
        'scss/map-keys-quotes': 'always',
        'scss/no-duplicate-dollar-variables': [true, {
          ignoreInsideAtRules: ['if', 'mixin'],
          ignoreDefaults: false,
        }],
        'scss/no-duplicate-load-rules': true,
        'scss/no-unused-private-members': true,
        'scss/partial-no-import': true,
        'scss/at-import-partial-extension-disallowed-list': ['scss'],
        // eslint-disable-next-line unicorn/no-null -- Null is required here
        'property-no-unknown': null,
        'scss/property-no-unknown': true,
        'scss/selector-no-redundant-nesting-selector': true,
      },
      overrides: [
        {
          name: buildOverrideName(OVERRIDES.SCSS),
          files: ['**/*.scss'],
          rules: {
            'scss/comment-no-loud': true,
          },
        },
      ],
    },
  ];
};
