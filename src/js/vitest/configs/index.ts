/**
 * @internal @brnshkr/config/vitest
 */

import { environment } from './environment';
import { spelling } from './spelling';
import { test } from './test';
import { ui } from './ui';

export const configs = <const>{
  environment,
  spelling,
  test,
  ui,
};
