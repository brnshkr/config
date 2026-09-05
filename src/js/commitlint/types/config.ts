/**
 * @internal @brnshkr/config/commitlint
 */

import type { RulesConfig, UserConfig } from '@commitlint/types';

export type Config = {
  [Key in keyof UserConfig as string extends Key ? never : Key]: UserConfig[Key];
};

export type Rules = Partial<RulesConfig>;
