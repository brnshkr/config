import { packageOrganization } from '../../shared/utils/package-json';

import { builtinConfig } from './builtin';
import { comments } from './comments';
import { css } from './css';
import { gitignore } from './gitignore';
import { ignores } from './ignores';
import { imports } from './import';
import { javascript } from './javascript';
import { jsdoc } from './jsdoc';
import { json } from './json';
import { markdown } from './markdown';
import { node } from './node';
import { overrides } from './overrides';
import { perfectionist } from './perfectionist';
import { regexp } from './regexp';
import { style } from './style';
import { svelte } from './svelte';
import { test } from './test';
import { toml } from './toml';
import { typescript } from './typescript';
import { unicorn } from './unicorn';
import { yaml } from './yaml';

export const configs = <const>{
  [packageOrganization]: builtinConfig[packageOrganization],
  comments,
  css,
  gitignore,
  ignores,
  import: imports,
  javascript,
  jsdoc,
  json,
  markdown,
  node,
  overrides,
  perfectionist,
  regexp,
  style,
  svelte,
  test,
  toml,
  typescript,
  unicorn,
  yaml,
};
