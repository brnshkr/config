/**
 * @internal @brnshkr/config/stylelint
 */

import { objectEntries, objectFromEntries } from '#shared/utils/object.ts';
import { TAILWIND_AT_RULE_PRELUDES, TAILWIND_SCOPING_AT_RULES } from '#shared/utils/tailwind.ts';
import { isModuleEnabled, MODULES, resolvePackages } from '#stylelint/utils/module.ts';

import type { Config } from '#stylelint/types/config.ts';

export const tailwind = (): Config[] => {
  const {
    optional: [hasConfigStandard],
  } = resolvePackages(MODULES.css);

  return [
    {
      languageOptions: {
        syntax: {
          atRules: objectFromEntries(objectEntries(TAILWIND_AT_RULE_PRELUDES).map(
            ([name, prelude]) => <const>[name, typeof prelude === 'string' ? { prelude } : {}],
          )),
        },
      },
      ...(hasConfigStandard
        ? {
          rules: {
            'import-notation': 'string',
            'nesting-selector-no-missing-scoping-root': [true, {
              ignoreAtRules: [
                ...TAILWIND_SCOPING_AT_RULES,
                ...(isModuleEnabled(MODULES.scss) ? ['mixin'] : []),
              ],
            }],
          },
        }
        : undefined),
    },
  ];
};
