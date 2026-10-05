/**
 * @internal @brnshkr/config/commitlint
 */

import { commit } from '#commitlint/configs/commit.ts';
import { conventional } from '#commitlint/configs/conventional.ts';
import { functions } from '#commitlint/configs/functions.ts';
import { tense } from '#commitlint/configs/tense.ts';

export const configs = <const>{
  commit,
  conventional,
  functions,
  tense,
};
