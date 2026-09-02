/**
 * @internal @brnshkr/config/markdownlint
 */

import { github } from './github';
import { ignores } from './ignores';
import { links } from './links';
import { markdown } from './markdown';
import { search } from './search';
import { style } from './style';
import { tables } from './tables';

export const configs = <const>{
  github,
  ignores,
  links,
  markdown,
  search,
  style,
  tables,
};
