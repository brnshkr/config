import { GLOB_IGNORES } from '../../shared/utils/globs';
import { MAIN_SCOPES, SUB_SCOPES } from '../types/scopes';
import { buildConfigName } from '../utils/config';

import type { Config } from '../types/config';

export const ignores = (customIgnores: string[] = []): Config[] => [
  {
    name: buildConfigName(MAIN_SCOPES.IGNORES, SUB_SCOPES.BASE),
    ignores: [
      ...GLOB_IGNORES,
      ...customIgnores,
    ],
  },
];
