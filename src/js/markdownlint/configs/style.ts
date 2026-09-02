/**
 * @internal @brnshkr/config/markdownlint
 */

import { resolveCustomRule } from '../utils/config';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const style = (): Config[] => {
  const {
    requiredAll: [isMarkdownlintRulesInstalled],
  } = resolvePackages(MODULES.style);

  if (!isMarkdownlintRulesInstalled) {
    return [];
  }

  return [
    {
      customRules: [
        resolveCustomRule(PACKAGES.MARKDOWNLINT_RULES),
      ],
      config: {
        'list-item-marker-space': false,
        'fenced-code-fence-length': false,
        'reference-link-section-placement': false,
        'heading-sentence-case': {
          // eslint-disable-next-line ts/naming-convention -- Option needs to be cased like this
          allowed_words: [
            'Composer',
            'Doctrine',
            'ESLint',
            'Laravel',
            'Makefile',
            'PHPStan',
            'PHPUnit',
            'Pest',
            'Rector',
            'Stylelint',
            'Symfony',
            'Tempest',
            'Twig',
            'Xdebug',
          ],
        },
      },
    },
  ];
};
