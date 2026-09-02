/**
 * @internal @brnshkr/config/markdownlint
 */

import type { Config as GeneratedConfig, Rules as GeneratedRules } from './declarations/typegen';
import type { CustomRules } from './rules';

export type Rules = GeneratedRules & Partial<CustomRules>;

export type Override = Omit<NonNullable<GeneratedConfig['overrides']>[number], 'config'> & {
  config: Rules;
};

export type Config = Omit<GeneratedConfig, 'config' | 'overrides'> & {
  config?: Rules;
  overrides?: Override[];
};
