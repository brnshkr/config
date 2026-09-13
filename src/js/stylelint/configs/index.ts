/**
 * @internal @brnshkr/config/stylelint
 */

import { baseline } from './baseline';
import { css } from './css';
import { defensive } from './defensive';
import { html } from './html';
import { ignores } from './ignores';
import { less } from './less';
import { modules } from './modules';
import { nesting } from './nesting';
import { order } from './order';
import { scss } from './scss';
import { strict } from './strict';
import { style } from './style';

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
