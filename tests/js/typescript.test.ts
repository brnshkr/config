import { execSync } from 'node:child_process';

import { expect, test } from 'vitest';

test('expected typescript config', () => {
  expect(execSync('bun tsc --showConfig', { encoding: 'utf8' }).replaceAll(process.cwd(), '.')).toMatchSnapshot();
});
