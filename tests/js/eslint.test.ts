import { execSync } from 'node:child_process';

import { expect, test } from 'vitest';

import { traverseDirectory } from './utils/filesystem';

test('expected eslint config', () => {
  traverseDirectory(`${process.cwd()}/tests/js/fixtures/eslint`, (filePath) => {
    expect(execSync(`bun lint:js --print-config ${filePath}`, { encoding: 'utf8' }).replaceAll(process.cwd(), '.')).toMatchSnapshot();
  });
});
