import { doAllPackagesExist } from '../../shared/utils/module';
import { objectFromEntries, objectKeys } from '../../shared/utils/object';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';
import { GLOB_CSS } from '../utils/globs';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { CSSLanguageOptions, DefaultSyntaxConfig, SyntaxExtensionCallback } from '@eslint/css';
import type { Config } from '../types/config';
import type { CssOptions } from '../types/options';

type CustomSyntax = NonNullable<CSSLanguageOptions['customSyntax']>;
type SyntaxDefinition = Exclude<CustomSyntax, SyntaxExtensionCallback>;

const buildTailwindSyntax = (defaultSyntax: DefaultSyntaxConfig): SyntaxDefinition => {
  const declarations = objectFromEntries(objectKeys(defaultSyntax.properties).map(
    (property) => <const>[property, '<declaration-value>'],
  ));

  return {
    atrules: {
      apply: {
        prelude: '<any-value>',
      },
      config: {
        prelude: '<string>',
      },
      'custom-variant': {
        prelude: '<any-value>',
      },
      plugin: {
        prelude: '<string>',
      },
      reference: {
        prelude: '<string>',
      },
      slot: {
        // eslint-disable-next-line unicorn/no-null -- The CSS syntax definition requires an explicit null to mark an at-rule as prelude-less
        prelude: null,
      },
      source: {
        prelude: '<any-value>',
      },
      theme: {
        prelude: '<any-value>?',
      },
      utility: {
        prelude: '<any-value>',
        descriptors: declarations,
      },
      variant: {
        prelude: '<any-value>',
        descriptors: declarations,
      },
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

const buildCustomSyntax = (
  customSyntax: CSSLanguageOptions['customSyntax'],
  isTailwindEnabled: boolean,
): CSSLanguageOptions['customSyntax'] => {
  if (!isTailwindEnabled) {
    return customSyntax;
  }

  return (defaultSyntax: DefaultSyntaxConfig): SyntaxDefinition => {
    const tailwindSyntax = buildTailwindSyntax(defaultSyntax);

    if (customSyntax === undefined) {
      return tailwindSyntax;
    }

    return mergeSyntaxDefinitions(
      tailwindSyntax,
      typeof customSyntax === 'function' ? customSyntax(defaultSyntax) : customSyntax,
    );
  };
};

export const css = async (options?: Partial<CssOptions>): Promise<Config[]> => {
  const {
    requiredAll: [pluginCss],
  } = await resolvePackages(MODULES.css);

  if (!pluginCss) {
    return [];
  }

  const isTailwindEnabled = options?.tailwind ?? doAllPackagesExist([PACKAGES.TAILWINDCSS]);
  const customSyntax = buildCustomSyntax(options?.customSyntax, isTailwindEnabled);

  return [
    {
      name: buildConfigName(MAIN_SCOPES.CSS, SUB_SCOPES.SETUP),
      plugins: {
        css: pluginCss,
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
        'css/relative-font-units': ['error', {
          allowUnits: ['em', 'rem'],
        }],
        'css/use-baseline': 'error',
      },
    },
  ];
};
