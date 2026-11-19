/**
 * @internal @brnshkr/config
 */

import { objectEntries, objectFromEntries } from '#shared/utils/object.ts';

import type { Maybe } from '#shared/types/core.ts';

export interface CssDialectAtRule {
  /**
   * The prelude syntax, left out for an at-rule without one.
   */
  prelude?: string;

  /**
   * Additional descriptors for its block.
   */
  descriptors?: Record<string, string>;

  /**
   * Enables every property as a declaration in its block.
   */
  isDeclarationBlock?: boolean;

  /**
   * Enables nesting selectors directly in its block.
   */
  isScopingRoot?: boolean;
}

export interface CssDialect {
  /**
   * Additional at-rules, keyed by name without the `@`.
   */
  atRules: Record<string, CssDialectAtRule>;

  /**
   * Additional or redefined properties.
   */
  properties: Record<string, string>;

  /**
   * Additional functions, keyed by name with parentheses, e.g. `'acme-tint()'`.
   */
  functions: Record<string, string>;

  /**
   * Additional or redefined value types.
   */
  types: Record<string, string>;
}

export const mergeDialects = (dialects: Partial<CssDialect>[]): CssDialect => ({
  atRules: objectFromEntries(dialects.flatMap(({ atRules }) => objectEntries(atRules ?? {}))),
  properties: objectFromEntries(dialects.flatMap(({ properties }) => objectEntries(properties ?? {}))),
  functions: objectFromEntries(dialects.flatMap(({ functions }) => objectEntries(functions ?? {}))),
  types: objectFromEntries(dialects.flatMap(({ types }) => objectEntries(types ?? {}))),
});

export const getScopingRootAtRules = (
  dialect: Maybe<CssDialect>,
): string[] => objectEntries(dialect?.atRules ?? {})
  .filter(([, { isScopingRoot }]) => isScopingRoot === true)
  .map(([name]) => name);
