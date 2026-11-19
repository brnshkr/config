/**
 * @internal @brnshkr/config/stylelint
 */

import { packageOrganization } from '#shared/utils/package-json.ts';
import { baseline } from '#stylelint/configs/baseline.ts';
import { builtin } from '#stylelint/configs/builtin/index.ts';
import { css } from '#stylelint/configs/css.ts';
import { defensive } from '#stylelint/configs/defensive.ts';
import { html } from '#stylelint/configs/html.ts';
import { ignores } from '#stylelint/configs/ignores.ts';
import { less } from '#stylelint/configs/less.ts';
import { markdown } from '#stylelint/configs/markdown.ts';
import { modules } from '#stylelint/configs/modules.ts';
import { nesting } from '#stylelint/configs/nesting.ts';
import { order } from '#stylelint/configs/order.ts';
import { scss } from '#stylelint/configs/scss.ts';
import { strict } from '#stylelint/configs/strict.ts';
import { style } from '#stylelint/configs/style.ts';
import { tailwind } from '#stylelint/configs/tailwind.ts';

export const configs = <const>{
  [packageOrganization]: builtin,
  baseline,
  css,
  defensive,
  html,
  ignores,
  less,
  markdown,
  modules,
  nesting,
  order,
  scss,
  strict,
  style,
  tailwind,
};
