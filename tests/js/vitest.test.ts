import { expect, test } from 'vitest';

import { getConfig } from '../../src/js/vitest';

test('expected vitest config', () => {
  const config = JSON.parse(JSON.stringify(getConfig()).replaceAll(process.cwd(), '<root>'));

  expect(config).toMatchSnapshot();
});
