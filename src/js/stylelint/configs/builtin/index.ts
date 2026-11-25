/**
 * @internal @brnshkr/config/stylelint
 */

import { GLOB_PHP_TEMPLATE_FILES } from '#shared/utils/globs.ts';
import { fencedCodeSyntax, GLOB_FENCED_CODE_FILES } from '#stylelint/configs/builtin/fenced-code.ts';
import { OVERRIDES } from '#stylelint/types/overrides.ts';
import { buildOverrideName } from '#stylelint/utils/config.ts';
import { isModuleEnabled, MODULES, PACKAGES } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

export const builtin = (): Config[] => [
  {
    overrides: [
      {
        name: buildOverrideName(OVERRIDES.FENCED_CODE),
        files: [...GLOB_FENCED_CODE_FILES],
        customSyntax: fencedCodeSyntax,
        rules: {
          // eslint-disable-next-line unicorn/no-null -- Null is required here
          'no-empty-source': null,
        },
      },
      ...isModuleEnabled(MODULES.html)
        ? [
          {
            name: buildOverrideName(OVERRIDES.PHP_TEMPLATE),
            files: [...GLOB_PHP_TEMPLATE_FILES],
            customSyntax: PACKAGES.POSTCSS_HTML,
          },
        ]
        : [],
    ],
  },
];
