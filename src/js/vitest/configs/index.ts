/**
 * @internal @brnshkr/config/vitest
 */

import { environment } from '#vitest/configs/environment.ts';
import { spelling } from '#vitest/configs/spelling.ts';
import { test } from '#vitest/configs/test.ts';
import { ui } from '#vitest/configs/ui.ts';

export const configs = <const>{
  environment,
  spelling,
  test,
  ui,
};
