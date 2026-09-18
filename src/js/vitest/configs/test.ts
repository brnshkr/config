/**
 * @internal @brnshkr/config/vitest
 */

import { GLOB_IGNORES, GLOB_TEST_FILES } from '../../shared/utils/globs';
import { packageOrganization } from '../../shared/utils/package-json';

import type { Config } from '../types/config';

const NARROWED_IGNORES: Record<string, string[]> = {
  '**/dist/**': [
    'dist/**',
  ],
  '**/node_modules/**': [
    `**/node_modules/!(@${packageOrganization})/**`,
    `**/node_modules/@${packageOrganization}/*/!(dist)/**`,
  ],
};

export const test = (): Config[] => [
  {
    resolve: {
      tsconfigPaths: true,
    },
    test: {
      include: GLOB_TEST_FILES,
      exclude: GLOB_IGNORES.flatMap((glob) => NARROWED_IGNORES[glob] ?? glob),
    },
  },
];
