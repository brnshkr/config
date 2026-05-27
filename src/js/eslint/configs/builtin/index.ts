import { packageOrganization, packageOrganizationUpper, packageVersion } from '../../../shared/utils/package-json';
import { MAIN_SCOPES, SUB_SCOPES } from '../../types/scopes';
import { buildConfigName } from '../../utils/config';
import { GLOB_SCRIPT_FILES } from '../../utils/globs';
import { resolveTsConfigPath } from '../../utils/tsconfig';

import { apiOrInternalTagRule } from './api-or-internal-tag';
import { publicApiDocumentationRule } from './public-api-documentation';
import { requireImportAliasRule } from './require-import-alias';
import { requireImportAttributesRule } from './require-import-attributes';

import type { ESLint } from 'eslint';
import type { Config } from '../../types/config';
import type { TypescriptOptions } from '../../types/options';

type ExtractValueTypeFromRecord<TRecord> = TRecord extends Record<string, infer U> ? U : never;

export type RuleDefinition = ExtractValueTypeFromRecord<ESLint.Plugin['rules']>;

export const RULE_DEFINITIONS = <const>{
  'public-api-documentation': publicApiDocumentationRule,
  'require-import-attributes': requireImportAttributesRule,
  'require-import-alias': requireImportAliasRule,
  'api-or-internal-tag': apiOrInternalTagRule,
} satisfies Record<string, RuleDefinition>;

const builtin = (typescriptOptions?: boolean | Partial<TypescriptOptions>): Config[] => [
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
      [<const>`${packageOrganization}/public-api-documentation`]: 'error',
      [<const>`${packageOrganization}/require-import-alias`]: ['error', {
        tsConfigPath: resolveTsConfigPath(typeof typescriptOptions === 'object' ? typescriptOptions : undefined),
      }],
      [<const>`${packageOrganization}/require-import-attributes`]: 'error',
      [<const>`${packageOrganization}/api-or-internal-tag`]: 'error',
    } satisfies Required<Pick<NonNullable<Config['rules']>, `${typeof packageOrganization}/${keyof typeof RULE_DEFINITIONS}`>>,
  },
];

export const builtinConfig = {
  [packageOrganization]: builtin,
};
