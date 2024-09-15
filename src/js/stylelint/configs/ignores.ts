import { GLOB_IGNORES } from '../../shared/utils/globs';

import type { Config } from '../types/config';

export const ignores = (): Config[] => [
  {
    ignoreFiles: GLOB_IGNORES.map((glob) => glob.replace(/^\*\*/v, process.cwd())),
  },
];
