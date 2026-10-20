/**
 * @internal @brnshkr/config/stylelint
 */

import { packageOrganization } from '#shared/utils/package-json.ts';

import {
  createFencedCodePlugin,
  GLOB_FENCED_CODE_BLOCKS,
  GLOB_FENCED_CODE_FILES,
} from '#stylelint/configs/builtin/fenced-code.ts';

import { OVERRIDES } from '#stylelint/types/overrides.ts';
import { buildOverrideName } from '#stylelint/utils/config.ts';

import type { Config } from '#stylelint/types/config.ts';

export const markdown = (ownConfig: Config): Config[] => [
  {
    plugins: [createFencedCodePlugin(ownConfig)],
    overrides: [
      {
        name: buildOverrideName(OVERRIDES.FENCED_CODE_RULE),
        files: [...GLOB_FENCED_CODE_FILES],
        rules: {
          [`${packageOrganization}/fenced-code`]: true,
        },
      },
      {
        name: buildOverrideName(OVERRIDES.FENCED_CODE_BLOCKS),
        files: [GLOB_FENCED_CODE_BLOCKS],
        rules: {
          /* eslint-disable unicorn/no-null -- Null is required here */
          'no-unknown-animations': null,
          'no-unknown-custom-media': null,
          'no-unknown-custom-properties': null,
          /* eslint-enable unicorn/no-null */
        },
      },
    ],
  },
];
