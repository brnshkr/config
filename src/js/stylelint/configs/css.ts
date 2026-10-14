/**
 * @internal @brnshkr/config/stylelint
 */

import { getScopingRootAtRules, mergeDialects } from '#shared/utils/dialect.ts';
import { objectEntries, objectFromEntries, objectKeys } from '#shared/utils/object.ts';
import { TAILWIND_DIALECT } from '#shared/utils/tailwind.ts';

import {
  isModuleEnabled,
  MODULES,
  PACKAGES,
  resolvePackages,
} from '#stylelint/utils/module.ts';

import type { CssDialect } from '#shared/utils/dialect.ts';
import type { Config } from '#stylelint/types/config.ts';

export const css = (projectDialect: Partial<CssDialect>): Config[] => {
  const {
    optional: [hasConfigStandard],
  } = resolvePackages(MODULES.css);

  const dialect = mergeDialects([
    ...(isModuleEnabled(MODULES.tailwind) ? [TAILWIND_DIALECT] : []),
    projectDialect,
  ]);

  const scopingRootAtRules = getScopingRootAtRules(dialect);

  const functionNames = objectKeys(dialect.functions).map(
    (functionName) => functionName.replace('()', ''),
  );

  return [
    {
      ...(hasConfigStandard
        ? {
          extends: PACKAGES.STYLELINT_CONFIG_STANDARD,
        }
        : undefined),
      reportDescriptionlessDisables: true,
      reportInvalidScopeDisables: true,
      reportNeedlessDisables: true,
      reportUnscopedDisables: true,
      languageOptions: {
        directionality: {
          block: 'top-to-bottom',
          inline: 'left-to-right',
        },
        syntax: {
          atRules: objectFromEntries(objectEntries(dialect.atRules).map(
            ([name, { prelude, descriptors }]) => <const>[name, {
              ...(prelude === undefined ? undefined : { prelude }),
              ...(descriptors === undefined ? undefined : { descriptors }),
            }],
          )),
          properties: dialect.properties,
          types: {
            ...dialect.functions,
            ...dialect.types,
          },
        },
      },
      rules: {
        'at-rule-disallowed-list': [
          'debug',
        ],
        'at-rule-property-required-list': {
          'font-face': [
            'font-display',
            'font-family',
            'font-style',
            'font-weight',
            'src',
          ],
        },
        'color-hex-length': 'long',
        'color-named': 'never',
        'color-no-invalid-hex': true,
        // eslint-disable-next-line security/detect-non-literal-regexp -- Split so that a project-wide search for the marker skips this line
        'comment-word-disallowed-list': [new RegExp(`^${['TO', 'DO'].join('')}`, 'v')],
        'declaration-no-important': true,
        'declaration-property-value-disallowed-list': {
          border: ['none'],
          'border-top': ['none'],
          'border-right': ['none'],
          'border-bottom': ['none'],
          'border-left': ['none'],
        },
        'declaration-property-unit-allowed-list': {
          '/^animation/': 'ms',
          '/^transition/': 'ms',
          'font-size': ['em', 'rem'],
          'line-height': [],
        },
        'font-family-name-quotes': 'always-unless-keyword',
        'font-weight-notation': 'numeric',
        'function-linear-gradient-no-nonstandard-direction': true,
        'function-no-unknown': [true, {
          ignoreFunctions: functionNames,
        }],
        'function-url-no-scheme-relative': true,
        'function-url-scheme-allowed-list': ['data', 'https'],
        'max-nesting-depth': 3,
        ...((hasConfigStandard && scopingRootAtRules.length > 0)
          ? {
            'nesting-selector-no-missing-scoping-root': [true, {
              ignoreAtRules: [
                ...scopingRootAtRules,
                ...(isModuleEnabled(MODULES.scss) ? ['mixin'] : []),
              ],
            }],
          }
          : undefined),
        'no-unknown-animations': true,
        'no-unknown-custom-media': true,
        'no-unknown-custom-properties': true,
        'property-layout-mappings': 'flow-relative',
        'relative-selector-nesting-notation': 'implicit',
        'selector-class-pattern': [
          '^[a-z]([-]?[a-z0-9]+)*(__[a-z0-9]([-]?[a-z0-9]+)*)?(--[a-z0-9]([-]?[a-z0-9]+)*)?$',
          {
            resolveNestedSelectors: true,
            message: (value: string): string => `Expected class selector "${value}" to match BEM methodology (https://getbem.com/). Selector validation tool: https://regexr.com/3apms`,
          },
        ],
        'selector-no-deprecated': true,
        'selector-no-invalid': true,
        'selector-no-unmatchable': true,
        'string-no-newline': true,
        'unit-layout-mappings': 'flow-relative',
        'unit-no-unknown': true,
        'value-keyword-case': ['lower', {
          camelCaseSvgKeywords: true,
          ignoreKeywords: [/^geometricPrecision$/v],
        }],
        'value-keyword-layout-mappings': 'flow-relative',
      },
    },
  ];
};
