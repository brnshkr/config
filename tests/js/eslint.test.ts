import { execSync } from 'node:child_process';

import { expect, test } from 'vitest';

import { traverseDirectory } from './utils/filesystem';

test('expected eslint config', () => {
  traverseDirectory(`${process.cwd()}/tests/js/fixtures/eslint`, (filePath) => {
    const result = execSync(`bun lint:js --print-config ${filePath}`, { encoding: 'utf8' })
      .replaceAll(process.cwd(), '.')
      // NOTICE: Replace version of builtin plugin to avoid snapshot updates on every release
      .replaceAll(/"brnshkr:brnshkr@.*",/gv, '"brnshkr:brnshkr@<version>",');

    expect(result).toMatchSnapshot();
  });
});
