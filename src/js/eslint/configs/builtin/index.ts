/**
 * @internal @brnshkr/config/eslint
 */

import { packageOrganization, packageOrganizationUpper, packageVersion } from '../../../shared/utils/package-json';
import { MAIN_SCOPES, SUB_SCOPES } from '../../types/scopes';
import { buildConfigName } from '../../utils/config';
import { GLOB_SCRIPT_FILES } from '../../utils/globs';
import { resolveTsConfigPath } from '../../utils/tsconfig';

import { apiOrInternalTagRule } from './api-or-internal-tag';
import { boolishPrefixRule } from './boolish-prefix';
import { interfaceSuffixRule } from './interface-suffix';
import { internalUsageRule } from './internal-usage';
import { publicApiDocumentationRule } from './public-api-documentation';
import { requireImportAliasRule } from './require-import-alias';
import { requireImportAttributesRule } from './require-import-attributes';
// eslint-disable-next-line unicorn/prevent-abbreviations -- Mirrors the rule id, which pairs with the PHP `ResolvableDocReferenceRule`
import { resolvableDocReferenceRule } from './resolvable-doc-reference';

import type { ESLint } from 'eslint';
import type { Config } from '../../types/config';
import type { TypescriptOptions } from '../../types/options';

type ExtractValueTypeFromRecord<TRecord> = TRecord extends Record<string, infer U> ? U : never;

export type RuleDefinition = ExtractValueTypeFromRecord<ESLint.Plugin['rules']>;

export const RULE_DEFINITIONS = <const>{
  'api-or-internal-tag': apiOrInternalTagRule,
  'boolish-prefix': boolishPrefixRule,
  'interface-suffix': interfaceSuffixRule,
  'internal-usage': internalUsageRule,
  'public-api-documentation': publicApiDocumentationRule,
  'require-import-attributes': requireImportAttributesRule,
  'require-import-alias': requireImportAliasRule,
  'resolvable-doc-reference': resolvableDocReferenceRule,
} satisfies Record<string, RuleDefinition>;

const builtin = (typescriptOptions?: boolean | Partial<TypescriptOptions>): Config[] => {
  const tsConfigPath = resolveTsConfigPath(typeof typescriptOptions === 'object' ? typescriptOptions : undefined);

  return [
    {
      name: buildConfigName(MAIN_SCOPES[packageOrganizationUpper], SUB_SCOPES.SETUP),
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
      name: buildConfigName(MAIN_SCOPES[packageOrganizationUpper], SUB_SCOPES.RULES),
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
      } satisfies Required<Pick<NonNullable<Config['rules']>, `${typeof packageOrganization}/${keyof typeof RULE_DEFINITIONS}`>>,
    },
  ];
};

export const builtinConfig = {
  [packageOrganization]: builtin,
};
