import { execSync } from 'node:child_process';

import { expect, test } from 'vitest';

import { traverseDirectory } from './utils/filesystem';

test('expected stylelint config', () => {
  traverseDirectory(`${process.cwd()}/tests/js/fixtures/stylelint`, (filePath) => {
    expect(execSync(`bun lint:css --print-config ${filePath}`, { encoding: 'utf8' }).replaceAll(process.cwd(), '.')).toMatchSnapshot();
  });
});
