/**
 * @internal @brnshkr/config/eslint
 */

import type { Rule } from 'eslint';
import type { RuleDefinition } from '#eslint/configs/builtin/index.ts';
import type { Maybe } from '#shared/types/core.ts';

export type ReportRewrite = (
  descriptor: Rule.ReportDescriptor,
  context: Rule.RuleContext,
) => Maybe<Rule.ReportDescriptor>;

export const rewriteRuleReports = <TRule extends RuleDefinition>(
  rule: TRule,
  rewrite: ReportRewrite,
): TRule => ({
  ...rule,
  // eslint-disable-next-line ts/no-unsafe-type-assertion -- The original context stays the prototype
  create: (context: Rule.RuleContext) => rule.create(<Rule.RuleContext>Object.create(context, {
    report: {
      value: (descriptor: Rule.ReportDescriptor): void => {
        const rewrittenDescriptor = rewrite(descriptor, context);

        if (rewrittenDescriptor !== undefined) {
          context.report(rewrittenDescriptor);
        }
      },
    },
  })),
});
