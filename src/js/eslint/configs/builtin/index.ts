/**
 * @internal @brnshkr/config/eslint
 */

import { apiOrInternalTagRule } from '#eslint/configs/builtin/api-or-internal-tag.ts';
import { boolishPrefixRule } from '#eslint/configs/builtin/boolish-prefix.ts';
import { interfaceSuffixRule } from '#eslint/configs/builtin/interface-suffix.ts';
import { internalUsageRule } from '#eslint/configs/builtin/internal-usage.ts';
import { publicApiDocumentationRule } from '#eslint/configs/builtin/public-api-documentation.ts';
import { requireImportAliasRule } from '#eslint/configs/builtin/require-import-alias.ts';
import { requireImportAttributesRule } from '#eslint/configs/builtin/require-import-attributes.ts';
import { resolvableDocReferenceRule } from '#eslint/configs/builtin/resolvable-doc-reference.ts';
import { typeAssertionStyleRule } from '#eslint/configs/builtin/type-assertion-style.ts';
import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName } from '#eslint/utils/config.ts';
import { GLOB_SCRIPT_FILES } from '#eslint/utils/globs.ts';
import { resolveTsConfigPath } from '#eslint/utils/tsconfig.ts';
import { packageOrganization, packageOrganizationUpper, packageVersion } from '#shared/utils/package-json.ts';

import type { ESLint } from 'eslint';
import type { Config } from '#eslint/types/config.ts';
import type { TypescriptOptions } from '#eslint/types/options.ts';

type ExtractValueTypeFromRecord<TRecord> = TRecord extends Record<string, infer U> ? U : never;

export type RuleDefinition = ExtractValueTypeFromRecord<ESLint.Plugin['rules']>;

// eslint-disable-next-line security/detect-object-injection -- The key is the package's own organization name
const BUILTIN_SCOPE = MAIN_SCOPES[packageOrganizationUpper];

export const RULE_DEFINITIONS = <const>{
  'api-or-internal-tag': apiOrInternalTagRule,
  'boolish-prefix': boolishPrefixRule,
  'interface-suffix': interfaceSuffixRule,
  'internal-usage': internalUsageRule,
  'public-api-documentation': publicApiDocumentationRule,
  'require-import-attributes': requireImportAttributesRule,
  'require-import-alias': requireImportAliasRule,
  'resolvable-doc-reference': resolvableDocReferenceRule,
  'type-assertion-style': typeAssertionStyleRule,
} satisfies Record<string, RuleDefinition>;

export const builtin = (typescriptOptions?: boolean | Partial<TypescriptOptions>): Config[] => {
  const tsConfigPath = resolveTsConfigPath(typeof typescriptOptions === 'object' ? typescriptOptions : undefined);

  return [
    {
      name: buildConfigName(BUILTIN_SCOPE, SUB_SCOPES.SETUP),
      plugins: {
        [packageOrganization]: {
          meta: {
            name: packageOrganization,
            version: packageVersion,
          },
          rules: RULE_DEFINITIONS,
        },
      },
    },
    {
      name: buildConfigName(BUILTIN_SCOPE, SUB_SCOPES.RULES),
      files: GLOB_SCRIPT_FILES,
      rules: {
        [<const>`${packageOrganization}/api-or-internal-tag`]: 'error',
        [<const>`${packageOrganization}/boolish-prefix`]: 'error',
        [<const>`${packageOrganization}/interface-suffix`]: 'error',
        [<const>`${packageOrganization}/internal-usage`]: ['error', {
          tsConfigPath,
        }],
        [<const>`${packageOrganization}/public-api-documentation`]: 'error',
        [<const>`${packageOrganization}/require-import-alias`]: ['error', {
          tsConfigPath,
        }],
        [<const>`${packageOrganization}/require-import-attributes`]: 'error',
        [<const>`${packageOrganization}/resolvable-doc-reference`]: 'error',
        [<const>`${packageOrganization}/type-assertion-style`]: 'error',
      } satisfies Required<Pick<NonNullable<Config['rules']>, `${typeof packageOrganization}/${keyof typeof RULE_DEFINITIONS}`>>,
    },
  ];
};
