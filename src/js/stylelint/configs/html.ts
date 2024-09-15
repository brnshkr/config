import { OVERRIDES } from '../types/overrides';
import { buildOverrideName } from '../utils/config';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { Config } from '../types/config';

export const html = (): Config[] => {
  const {
    requiredAll: [isPostcssHtmlInstalled, isStylelintConfigHtmlInstalled],
  } = resolvePackages(MODULES.html);

  if (!isPostcssHtmlInstalled || !isStylelintConfigHtmlInstalled) {
    return [];
  }

  return [
    {
      extends: PACKAGES.STYLELINT_CONFIG_HTML,
      overrides: [
        {
          name: buildOverrideName(OVERRIDES.SVELTE),
          files: ['**/*.svelte'],
          rules: {
            'keyframes-name-pattern': [
              '^(-global-)?([a-z][a-z0-9]*)(-[a-z0-9]+)*$',
              {
                message: (name: string): string => `Expected keyframe name "${name}" to be kebab-case`,
              },
            ],
            'selector-pseudo-class-no-unknown': [true, {
              ignorePseudoClasses: [
                'global',
              ],
            }],
          },
        },
        // eslint-disable-next-line no-warning-comments -- (vue-support)
        // TODO: This will be needed if and when vue support is added
        // {
        //   name: buildOverrideName(OVERRIDES.VUE),
        //   files: ['**/*.vue'],
        //   rules: {
        //     'declaration-property-value-no-unknown': [true, {
        //       ignoreProperties: {
        //         '/.*/': String.raw`/v-bind\(.+\)/`,
        //       },
        //     }],
        //     'function-no-unknown': [true, {
        //       ignoreFunctions: [
        //         'v-bind',
        //       ],
        //     }],
        //     'selector-pseudo-class-no-unknown': [true, {
        //       ignorePseudoClasses: [
        //         'deep',
        //         'global',
        //         'slotted',
        //       ],
        //     }],
        //   },
        // },
      ],
    },
  ];
};
