/**
 * @internal @brnshkr/config/stylelint
 */

import { INDENT, MAX_LEN, QUOTES } from '#shared/utils/constants.ts';
import { objectEntries, objectFromEntries } from '#shared/utils/object.ts';
import { GLOB_FENCED_CODE_FILES } from '#stylelint/configs/builtin/fenced-code.ts';
import { OVERRIDES } from '#stylelint/types/overrides.ts';
import { buildOverrideName } from '#stylelint/utils/config.ts';
import { MODULES, PACKAGES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

const STYLISTIC_RULES = {
  'at-rule-name-newline-after': 'always-multi-line',
  'at-rule-semicolon-space-before': 'never',
  'block-closing-brace-newline-before': 'always',
  'block-opening-brace-newline-after': 'always',
  'declaration-block-semicolon-newline-after': 'always',
  'function-comma-newline-before': 'never-multi-line',
  'function-comma-space-before': 'never-single-line',
  indentation: INDENT,
  linebreaks: 'unix',
  'max-line-length': MAX_LEN,
  'media-query-list-comma-newline-after': 'always',
  'media-query-list-comma-newline-before': 'never-multi-line',
  'named-grid-areas-alignment': [
    true,
    {
      alignQuotes: true,
    },
  ],
  'selector-list-comma-newline-before': 'never-multi-line',
  'string-quotes': QUOTES,
  'unicode-bom': 'never',
  'value-list-comma-newline-before': 'never-multi-line',
  'value-list-comma-space-before': 'never-single-line',
} satisfies Config['rules'];

const getStylisticRules = (namespace?: string): NonNullable<Config['rules']> => objectFromEntries(
  objectEntries(STYLISTIC_RULES).map(([ruleName, setting]) => <const>[
    namespace === undefined
      ? `@stylistic/${ruleName}`
      : `@stylistic/${namespace}/${ruleName}`,
    setting,
  ]),
);

export const style = (): Config[] => {
  const {
    requiredAll: [isStylisticStylelintConfigInstalled],
  } = resolvePackages(MODULES.style);

  if (!isStylisticStylelintConfigInstalled) {
    return [];
  }

  return [
    {
      extends: PACKAGES.STYLISTIC_STYLELINT_CONFIG,
      rules: getStylisticRules(),
      overrides: [
        {
          name: buildOverrideName(OVERRIDES.STYLE_SCSS),
          files: ['**/*.scss'],
          rules: getStylisticRules('scss'),
        },
        {
          name: buildOverrideName(OVERRIDES.STYLE_LESS),
          files: ['**/*.less'],
          rules: getStylisticRules('less'),
        },
        {
          name: buildOverrideName(OVERRIDES.STYLE_FENCED_CODE),
          files: [...GLOB_FENCED_CODE_FILES],
          rules: {
            /* eslint-disable unicorn/no-null -- Null is required here */
            '@stylistic/max-empty-lines': null,
            '@stylistic/max-line-length': null,
            /* eslint-enable unicorn/no-null */
          },
        },
      ],
    },
  ];
};
