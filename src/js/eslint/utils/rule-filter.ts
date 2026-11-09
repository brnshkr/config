/**
 * @internal @brnshkr/config/eslint
 */

import type { Rule } from 'eslint';
import type { RuleDefinition } from '#eslint/configs/builtin/index.ts';

export const filterRuleReports = <TRule extends RuleDefinition>(
  rule: TRule,
  isKept: (descriptor: Rule.ReportDescriptor) => boolean,
): TRule => ({
  ...rule,
  create: (context: Rule.RuleContext) => rule.create(<Rule.RuleContext>Object.create(context, {
    report: {
      value: (descriptor: Rule.ReportDescriptor): void => {
        if (isKept(descriptor)) {
          context.report(descriptor);
        }
      },
    },
  })),
});
