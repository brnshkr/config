/**
 * @internal @brnshkr/config/markdownlint
 */

import type { Rule } from 'markdownlint';
import type { RULE_DEFINITIONS } from '#markdownlint/configs/builtin/index.ts';
import type { Config as GeneratedConfig, Rules as GeneratedRules } from '#markdownlint/types/declarations/typegen.d.ts';
import type { CustomRules } from '#markdownlint/types/rules.ts';
import type { packageOrganization } from '#shared/utils/package-json.ts';

export type BuiltinRules = Record<`${typeof packageOrganization}/${keyof typeof RULE_DEFINITIONS}`, boolean>;
export type Rules = GeneratedRules & Partial<CustomRules> & Partial<BuiltinRules>;

export type Override = Omit<NonNullable<GeneratedConfig['overrides']>[number], 'config'> & {
  config: Rules;
};

export type Config = Omit<GeneratedConfig, 'config' | 'customRules' | 'overrides'> & {
  config?: Rules;
  customRules?: (string | Rule)[];
  overrides?: Override[];
};
