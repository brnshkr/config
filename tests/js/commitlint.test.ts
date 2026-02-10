import { execSync } from 'node:child_process';

import { expect, test } from 'vitest';

test('expected commitlint config', () => {
  const result = execSync(`bun lint:commit --print-config --color false`, { encoding: 'utf8' })
    .replaceAll(process.cwd(), '.');

  expect(result).toMatchSnapshot();
});
