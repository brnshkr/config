import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const strict = (): Config[] => {
  const {
    requiredAll: [isStylelintDeclarationStrictValueInstalled],
  } = resolvePackages(MODULES.strict);

  if (!isStylelintDeclarationStrictValueInstalled) {
    return [];
  }

  return [
    {
      plugins: PACKAGES.STYLELINT_DECLARATION_STRICT_VALUE,
      rules: {
        'scale-unlimited/declaration-strict-value': [
          '/color$/',
          'font-size',
          'z-index',
        ],
      },
    },
  ];
};
