import { expect, test } from 'vitest';

import { getConfig } from '#markdownlint/index.ts';

test('expected markdownlint config', () => {
  const config = JSON.parse(JSON.stringify(getConfig()).replaceAll(process.cwd(), '<root>'));

  expect(config).toMatchSnapshot();
});
