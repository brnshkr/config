/**
 * @internal @brnshkr/config/commitlint
 */

import { commit } from './commit';
import { conventional } from './conventional';
import { functions } from './functions';
import { tense } from './tense';

export const configs = <const>{
  commit,
  conventional,
  functions,
  tense,
};
