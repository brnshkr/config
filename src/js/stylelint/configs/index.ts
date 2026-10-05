/**
 * @internal @brnshkr/config/stylelint
 */

import { baseline } from '#stylelint/configs/baseline.ts';
import { css } from '#stylelint/configs/css.ts';
import { defensive } from '#stylelint/configs/defensive.ts';
import { html } from '#stylelint/configs/html.ts';
import { ignores } from '#stylelint/configs/ignores.ts';
import { less } from '#stylelint/configs/less.ts';
import { modules } from '#stylelint/configs/modules.ts';
import { nesting } from '#stylelint/configs/nesting.ts';
import { order } from '#stylelint/configs/order.ts';
import { scss } from '#stylelint/configs/scss.ts';
import { strict } from '#stylelint/configs/strict.ts';
import { style } from '#stylelint/configs/style.ts';

export const configs = <const>{
  baseline,
  css,
  defensive,
  html,
  ignores,
  less,
  modules,
  nesting,
  order,
  scss,
  strict,
  style,
};
