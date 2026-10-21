import {
  expect,
  onTestFinished,
  test,
  vi,
} from 'vitest';

import { getConfig } from '#vitest/index.ts';

test('expected vitest config', () => {
  vi.stubEnv('GITHUB_ACTIONS', '');

  onTestFinished(() => {
    vi.unstubAllEnvs();
  });

  const config = JSON.parse(JSON.stringify(getConfig()).replaceAll(process.cwd(), '<root>'));

  expect(config).toMatchSnapshot();
});
