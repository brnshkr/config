import { execSync } from 'node:child_process';

import { expect, test } from 'vitest';

test('expected commitlint config', () => {
  expect(execSync(`bun lint:commit --print-config --color false`, { encoding: 'utf8' }).replaceAll(process.cwd(), '.')).toMatchSnapshot();
});
