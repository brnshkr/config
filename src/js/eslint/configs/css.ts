/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName } from '#eslint/utils/config.ts';
import { GLOB_CSS } from '#eslint/utils/globs.ts';

import {
  isModuleEnabled,
  MODULES,
  resolvePackages,
} from '#eslint/utils/module.ts';

import { rewriteRuleReports } from '#eslint/utils/rule/reports.ts';
import { getAnyDescriptorAtRules, getScopingRootAtRules, mergeDialects } from '#shared/utils/dialect.ts';
import { objectEntries, objectFromEntries, objectKeys } from '#shared/utils/object.ts';
import { TAILWIND_DIALECT } from '#shared/utils/tailwind.ts';

import type { CSSLanguageOptions, DefaultSyntaxConfig, SyntaxExtensionCallback } from '@eslint/css';
import type PluginCssicorn from 'eslint-cssicorn';
import type { Config } from '#eslint/types/config.ts';
import type { CssOptions } from '#eslint/types/options.ts';
import type { Maybe } from '#shared/types/core.ts';
import type { CssDialect } from '#shared/utils/dialect.ts';

type CustomSyntax = NonNullable<CSSLanguageOptions['customSyntax']>;
type SyntaxDefinition = Exclude<CustomSyntax, SyntaxExtensionCallback>;

const buildDialectSyntax = (
  dialect: CssDialect,
  defaultSyntax: DefaultSyntaxConfig,
): SyntaxDefinition => {
  const declarations = objectFromEntries(objectKeys(defaultSyntax.properties).map(
    (property) => <const>[property, '<declaration-value>'],
  ));

  return {
    atrules: objectFromEntries(objectEntries(dialect.atRules).map(
      ([name, { prelude, descriptors, isDeclarationBlock }]) => <const>[
        name,
        {
          // eslint-disable-next-line unicorn/no-null -- The CSS syntax definition requires an explicit null to mark an at-rule as prelude-less
          prelude: prelude ?? null,
          descriptors: isDeclarationBlock === true
            ? {
              ...declarations,
              ...descriptors,
            }
            : descriptors,
        },
      ],
    )),
    properties: dialect.properties,
    types: {
      ...dialect.functions,
      ...dialect.types,
    },
  };
};

const mergeSyntaxDefinitions = (
  baseSyntax: SyntaxDefinition,
  overridingSyntax: SyntaxDefinition,
): SyntaxDefinition => ({
  ...baseSyntax,
  ...overridingSyntax,
  atrules: {
    ...baseSyntax.atrules,
    ...overridingSyntax.atrules,
  },
  properties: {
    ...baseSyntax.properties,
    ...overridingSyntax.properties,
  },
  types: {
    ...baseSyntax.types,
    ...overridingSyntax.types,
  },
});

const buildDialect = (
  isTailwindEnabled: boolean,
  options: Maybe<Partial<CssOptions>>,
): Maybe<CssDialect> => {
  const dialects = [
    ...(isTailwindEnabled
      ? [TAILWIND_DIALECT]
      : []),
    ...(options?.dialect === undefined
      ? []
      : [options.dialect]),
  ];

  return dialects.length === 0
    ? undefined
    : mergeDialects(dialects);
};

const buildCustomSyntax = (
  customSyntax: CSSLanguageOptions['customSyntax'],
  dialect: Maybe<CssDialect>,
): CSSLanguageOptions['customSyntax'] => {
  if (dialect === undefined) {
    return customSyntax;
  }

  return (defaultSyntax: DefaultSyntaxConfig): SyntaxDefinition => {
    const dialectSyntax = buildDialectSyntax(dialect, defaultSyntax);

    if (customSyntax === undefined) {
      return dialectSyntax;
    }

    return mergeSyntaxDefinitions(
      dialectSyntax,
      typeof customSyntax === 'function'
        ? customSyntax(defaultSyntax)
        : customSyntax,
    );
  };
};

const buildCssicornRules = (
  pluginCssicorn: Maybe<typeof PluginCssicorn>,
  dialect: Maybe<CssDialect>,
): Config['rules'] => (
  pluginCssicorn
    ? {
      ...pluginCssicorn.configs.recommended.rules,
      'cssicorn/no-layout-animations': 'error',
      'cssicorn/no-unscoped-nesting-selector': [
        'error',
        {
          scopingRootAtRules: getScopingRootAtRules(dialect),
        },
      ],
      'cssicorn/require-prefers-reduced-motion': 'error',
    }
    : undefined
);

export const css = async (options?: Partial<CssOptions>): Promise<Config[]> => {
  const {
    requiredAll: [pluginCss],
    optional: [pluginCssicorn],
  } = await resolvePackages(MODULES.css);

  if (!pluginCss) {
    return [];
  }

  const isTailwindEnabled = isModuleEnabled(MODULES.tailwind);
  const dialect = buildDialect(isTailwindEnabled, options);
  const customSyntax = buildCustomSyntax(options?.customSyntax, dialect);
  const anyDescriptorAtRules = new Set(getAnyDescriptorAtRules(dialect));

  return [
    {
      name: buildConfigName(MAIN_SCOPES.CSS, SUB_SCOPES.SETUP),
      plugins: {
        css: anyDescriptorAtRules.size === 0
          ? pluginCss
          : {
            ...pluginCss,
            rules: {
              ...pluginCss.rules,
              'no-invalid-at-rules': rewriteRuleReports(
                pluginCss.rules['no-invalid-at-rules'],
                (descriptor) => (('messageId' in descriptor
                  && descriptor.messageId === 'unknownDescriptor'
                  && anyDescriptorAtRules.has(String(descriptor.data?.['name']).toLowerCase()))
                  ? undefined
                  : descriptor),
              ),
            },
          },
        ...(pluginCssicorn
          ? {
            cssicorn: pluginCssicorn,
          }
          : undefined),
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.CSS, SUB_SCOPES.RULES),
      files: [GLOB_CSS],
      language: 'css/css',
      languageOptions: {
        ...(customSyntax === undefined
          ? undefined
          : {
            customSyntax,
          }),
        tolerant: options?.tolerant ?? isTailwindEnabled,
      } satisfies CSSLanguageOptions,
      rules: {
        ...pluginCss.configs.recommended.rules,
        ...(isTailwindEnabled
          ? {
            'css/no-duplicate-imports': 'off',
            'css/no-invalid-properties': 'off',
          }
          : undefined),
        'css/prefer-logical-properties': 'error',
        'css/relative-font-units': [
          'error',
          {
            allowUnits: ['em', 'rem'],
          },
        ],
        ...buildCssicornRules(pluginCssicorn, dialect),
        ...(isModuleEnabled(MODULES.unicorn)
          ? {
            'unicorn/no-missing-local-resource': 'error',
          }
          : undefined),
      },
    },
  ];
};
