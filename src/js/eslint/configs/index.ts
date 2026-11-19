/**
 * @internal @brnshkr/config/eslint
 */

import { builtin } from '#eslint/configs/builtin/index.ts';
import { comments } from '#eslint/configs/comments.ts';
import { css } from '#eslint/configs/css.ts';
import { html } from '#eslint/configs/html.ts';
import { ignores } from '#eslint/configs/ignores.ts';
import { imports } from '#eslint/configs/import.ts';
import { javascript } from '#eslint/configs/javascript.ts';
import { jsdoc } from '#eslint/configs/jsdoc.ts';
import { json } from '#eslint/configs/json.ts';
import { markdown } from '#eslint/configs/markdown.ts';
import { node } from '#eslint/configs/node.ts';
import { overrides } from '#eslint/configs/overrides.ts';
import { perfectionist } from '#eslint/configs/perfectionist.ts';
import { php } from '#eslint/configs/php.ts';
import { regexp } from '#eslint/configs/regexp.ts';
import { security } from '#eslint/configs/security.ts';
import { style } from '#eslint/configs/style.ts';
import { svelte } from '#eslint/configs/svelte.ts';
import { tailwind } from '#eslint/configs/tailwind.ts';
import { test } from '#eslint/configs/test.ts';
import { toml } from '#eslint/configs/toml.ts';
import { typescript } from '#eslint/configs/typescript.ts';
import { unicorn } from '#eslint/configs/unicorn.ts';
import { yaml } from '#eslint/configs/yaml.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

export const configs = <const>{
  [packageOrganization]: builtin,
  comments,
  css,
  html,
  ignores,
  import: imports,
  javascript,
  jsdoc,
  json,
  markdown,
  node,
  overrides,
  perfectionist,
  php,
  regexp,
  security,
  style,
  svelte,
  tailwind,
  test,
  toml,
  typescript,
  unicorn,
  yaml,
};
