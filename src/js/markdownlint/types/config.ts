/**
 * @internal @brnshkr/config/markdownlint
 */

import type { Rule } from 'markdownlint';
import type { packageOrganization } from '../../shared/utils/package-json';
import type { RULE_DEFINITIONS } from '../configs/builtin';
import type { Config as GeneratedConfig, Rules as GeneratedRules } from './declarations/typegen';
import type { CustomRules } from './rules';

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
