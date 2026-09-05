/**
 * @internal @brnshkr/config/markdownlint
 */

import { GLOB_IGNORES } from '../../shared/utils/globs';

import type { Config } from '../types/config';

// eslint-disable-next-line brnshkr/boolish-prefix -- Config builders are named after the config section they build
export const ignores = (): Config[] => [
  {
    gitignore: true,
    ignores: GLOB_IGNORES,
  },
];
