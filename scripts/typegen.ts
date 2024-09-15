import { write } from 'bun';
import { builtinRules } from 'eslint/use-at-your-own-risk';
import { flatConfigsToRulesDTS } from 'eslint-typegen/core';

import { getConfig } from '../src/js/eslint';

import type { Config } from '../src/js/eslint/types/config';

const allConfigs: Config[] = [
  {
    plugins: {
      '': {
        // eslint-disable-next-line ts/no-deprecated -- See: https://github.com/eslint/eslint/issues/18322#issuecomment-2053615962
        rules: Object.fromEntries(builtinRules.entries()),
      },
    },
  },
  ...await getConfig().toConfigs(),
];

const configNames = allConfigs.map(({ name }) => name).filter(Boolean);

const dts = `${
  await flatConfigsToRulesDTS(allConfigs, {
    includeAugmentation: false,
  })
}

export type ConfigNames = "${configNames.join('" | "')}"
`;

await write('src/js/eslint/types/declarations/typegen.d.ts', dts);
