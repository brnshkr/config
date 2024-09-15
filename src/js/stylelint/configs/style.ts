import { INDENT, MAX_LEN } from '../../shared/utils/constants';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

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
      rules: {
        '@stylistic/at-rule-name-newline-after': 'always-multi-line',
        '@stylistic/at-rule-semicolon-space-before': 'never',
        '@stylistic/block-closing-brace-newline-before': 'always',
        '@stylistic/block-opening-brace-newline-after': 'always',
        '@stylistic/declaration-block-semicolon-newline-after': 'always',
        '@stylistic/function-comma-newline-before': 'never-multi-line',
        '@stylistic/function-comma-space-before': 'never-single-line',
        '@stylistic/indentation': INDENT,
        '@stylistic/linebreaks': 'unix',
        '@stylistic/max-line-length': MAX_LEN,
        '@stylistic/media-query-list-comma-newline-after': 'always',
        '@stylistic/media-query-list-comma-newline-before': 'never-multi-line',
        '@stylistic/named-grid-areas-alignment': [true, {
          alignQuotes: true,
        }],
        '@stylistic/selector-list-comma-newline-before': 'never-multi-line',
        '@stylistic/string-quotes': 'single',
        '@stylistic/unicode-bom': 'never',
        '@stylistic/value-list-comma-newline-before': 'never-multi-line',
        '@stylistic/value-list-comma-space-before': 'never-single-line',
      },
    },
  ];
};
