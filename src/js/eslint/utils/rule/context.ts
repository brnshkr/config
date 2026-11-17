/**
 * @internal @brnshkr/config/eslint
 */

import type { ParserServicesWithTypeInformation, TSESLint } from '@typescript-eslint/utils';
import type { RuleDefinition } from '#eslint/configs/builtin/index.ts';
import type { PackageExportsResolverOptions } from '#eslint/utils/package/exports.ts';
import type { Maybe } from '#shared/types/core.ts';

export const toTypeScriptContext = (
  context: Parameters<RuleDefinition['create']>[0],
): TSESLint.RuleContext<string, unknown[]> => (
  // eslint-disable-next-line ts/no-unsafe-type-assertion -- Same object, typescript-eslint only types it differently
  <TSESLint.RuleContext<string, unknown[]>><unknown>context
);

export const getPackageExportsResolverOptions = (
  context: Parameters<RuleDefinition['create']>[0],
): PackageExportsResolverOptions => (
  // eslint-disable-next-line ts/no-unsafe-type-assertion -- Options are validated by the rule's schema
  <PackageExportsResolverOptions>(context.options[0] ?? {})
);

export const getTypeInformation = (sourceCode: TSESLint.SourceCode): Maybe<ParserServicesWithTypeInformation> => (
  sourceCode.parserServices?.program
    // eslint-disable-next-line ts/no-unsafe-type-assertion -- Services are complete once a program exists
    ? <ParserServicesWithTypeInformation>sourceCode.parserServices
    : undefined
);
