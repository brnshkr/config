/**
 * @internal @brnshkr/config/eslint
 */

import { GLOB_IGNORES } from '../../shared/utils/globs';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';

import type { Config } from '../types/config';

// eslint-disable-next-line brnshkr/boolish-prefix -- Config builders are named after the config section they build
export const ignores = (customIgnores: string[] = []): Config[] => [
  {
    name: buildConfigName(MAIN_SCOPES.IGNORES, SUB_SCOPES.BASE),
    ignores: [
      ...GLOB_IGNORES,
      ...customIgnores,
    ],
  },
];
