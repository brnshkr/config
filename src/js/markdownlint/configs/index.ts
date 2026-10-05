/**
 * @internal @brnshkr/config/markdownlint
 */

import { builtin } from '#markdownlint/configs/builtin/index.ts';
import { github } from '#markdownlint/configs/github.ts';
import { ignores } from '#markdownlint/configs/ignores.ts';
import { links } from '#markdownlint/configs/links.ts';
import { markdown } from '#markdownlint/configs/markdown.ts';
import { search } from '#markdownlint/configs/search.ts';
import { style } from '#markdownlint/configs/style.ts';
import { tables } from '#markdownlint/configs/tables.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

export const configs = <const>{
  [packageOrganization]: builtin,
  github,
  ignores,
  links,
  markdown,
  search,
  style,
  tables,
};
