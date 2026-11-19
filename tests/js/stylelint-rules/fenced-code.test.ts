import path from 'node:path';

import stylelint from 'stylelint';
import { expect, test } from 'vitest';

import { packageOrganization } from '#shared/utils/package-json.ts';
import { getConfig } from '#stylelint/index.ts';

const PAGE = [
  '# Acme',
  '',
  '```css',
  '.acme-card { colr: red; }',
  '```',
  '',
  '- An item:',
  '',
  '  ```scss',
  '  @use "sass:math";',
  '',
  '  .acme-card { padding: math.div(4px, 2); colr: red; }',
  '  ```',
  '',
  '```less',
  '@acme-gap: 4px;',
  '',
  '.acme-card { padding: @acme-gap; colr: red; }',
  '```',
  '',
  '```svelte',
  '<style>',
  '  .acme-card { colr: red; }',
  '</style>',
  '```',
  '',
  `<!-- ${packageOrganization}-skip -->`,
  '```css',
  '.acme-card { colr: red; }',
  '```',
  '',
  '```sass',
  '.acme-card',
  '  colr: red',
  '```',
  '',
].join('\n');

const SOURCE = [
  '/**',
  ' * Renders the card.',
  ' *',
  ' * @example',
  ' * ```css',
  ' * .acme-card { color: #FFFFFF; colr: red; }',
  ' * ```',
  ' */',
  'export const renderCard = (): void => {};',
  '',
].join('\n');

const lintCode = async (
  code: string,
  fileName: string,
  isFixing = false,
): Promise<stylelint.LinterResult> => stylelint.lint({
  code,
  codeFilename: path.join(process.cwd(), 'tests/js', fileName),
  config: getConfig(),
  fix: isFixing,
});

const getUnknownNameWarnings = ({ results }: stylelint.LinterResult): string[] => (results.at(0)?.warnings ?? [])
  .filter(({ rule }) => /(?:at-rule|property)-no-unknown$/v.test(rule))
  .map(({ line, column, rule }) => `${String(line)}:${String(column)} ${rule}`);

const getContextWarnings = ({ results }: stylelint.LinterResult): string[] => (results.at(0)?.warnings ?? [])
  .map(({ rule }) => rule)
  .filter((rule) => /^(?:defensive-css\/require-at-layer|no-unknown-(?:animations|custom-properties))$/v.test(rule));

test('createFencedCodePlugin reports each block of a page under the rule its dialect broke', async () => {
  expect(getUnknownNameWarnings(await lintCode(PAGE, 'page.md'))).toStrictEqual([
    '4:14 property-no-unknown',
    '12:43 scss/property-no-unknown',
    '18:34 property-no-unknown',
    '23:16 property-no-unknown',
  ]);
});

test('createFencedCodePlugin reports a docblock\'s block on its source line', async () => {
  expect(getUnknownNameWarnings(await lintCode(SOURCE, 'card.ts'))).toStrictEqual([
    '6:33 property-no-unknown',
  ]);
});

test('createFencedCodePlugin leaves out the rules that need the rest of the stylesheet', async () => {
  const code = [
    '.acme-card {',
    '  animation: acme-fade 200ms ease-in;',
    '  padding: var(--acme-gap);',
    '}',
  ].join('\n');

  const page = [
    '```css',
    code,
    '```',
    '',
  ].join('\n');

  expect(getContextWarnings(await lintCode(`${code}\n`, 'card.css'))).toStrictEqual([
    'defensive-css/require-at-layer',
    'no-unknown-animations',
    'no-unknown-custom-properties',
  ]);

  expect(getContextWarnings(await lintCode(page, 'page.md'))).toStrictEqual([]);
});

test('createFencedCodePlugin fixes each block in place', async () => {
  const { code } = await lintCode(SOURCE, 'card.ts', true);

  expect(code).toStrictEqual(SOURCE.replace(' * .acme-card { color: #FFFFFF; colr: red; }', () => [
    ' * .acme-card {',
    ' *   color: #ffffff;',
    ' *   colr: red;',
    ' * }',
  ].join('\n')));
});

test('createFencedCodePlugin fixes a page\'s block and a docblock\'s block opening with an empty line', async () => {
  const page = [
    '> ```css',
    '> .acme-card { color: #FFFFFF; }',
    '> ```',
    '',
  ].join('\n');

  const source = [
    '/**',
    ' * ```css',
    ' *',
    ' * .acme-card { color: #FFFFFF; }',
    ' * ```',
    ' */',
    'export const renderCard = (): void => {};',
    '',
  ].join('\n');

  const { code: fixedPage } = await lintCode(page, 'page.md', true);
  const { code: fixedSource } = await lintCode(source, 'card.ts', true);

  expect(fixedPage).toStrictEqual([
    '> ```css',
    '> .acme-card {',
    '>   color: #ffffff;',
    '> }',
    '> ```',
    '',
  ].join('\n'));

  expect(fixedSource).toStrictEqual([
    '/**',
    ' * ```css',
    ' * .acme-card {',
    ' *   color: #ffffff;',
    ' * }',
    ' * ```',
    ' */',
    'export const renderCard = (): void => {};',
    '',
  ].join('\n'));
});
