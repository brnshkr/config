/**
 * @internal @brnshkr/config/vitest
 */

import { packageFullName } from '#shared/utils/package-json.ts';

import type { Config } from '#vitest/types/config.ts';

export const spelling = (): Config[] => [
  {
    test: {
      include: [
        `**/node_modules/${packageFullName}/dist/spelling/spelling.test.mjs`,
      ],
    },
  },
];
