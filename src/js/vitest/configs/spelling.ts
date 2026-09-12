/**
 * @internal @brnshkr/config/vitest
 */

import { packageFullName } from '../../shared/utils/package-json';

import type { Config } from '../types/config';

export const spelling = (): Config[] => [
  {
    test: {
      include: [
        `**/node_modules/${packageFullName}/dist/spelling/spelling.test.mjs`,
      ],
    },
  },
];
