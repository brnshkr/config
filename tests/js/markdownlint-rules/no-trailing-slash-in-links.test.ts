import { applyFixes } from 'markdownlint';
import { lint } from 'markdownlint/sync';
import { expect, test } from 'vitest';

import { noTrailingSlashInLinksRule } from '#markdownlint/configs/builtin/no-trailing-slash-in-links.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

const PAGE = [
  '# Page',
  '',
  'A [docs](./docs/ "Docs") link and a [`docs/`](<./docs/>) one.',
  '',
  'An ![image](./images/) and an <https://example.com/a/> autolink.',
  '',
  'A bare https://example.com/b/ and www.example.com/c/ in prose.',
  '',
  'A [root](/) link, a [query](https://example.com/d/?e=1) and a [mail](<mailto:acme@example.com>) one.',
  '',
  'A `[code](./code/)` span and a [reference][r].',
  '',
  '```md',
  '[fence](./fence/)',
  '```',
  '',
  '[r]: https://example.com/f/',
  '',
].join('\n');

test('noTrailingSlashInLinksRule reports every link target ending in a slash and drops it as the fix', () => {
  const { page = [] } = lint({
    strings: { page: PAGE },
    config: {
      default: false,
      [`${packageOrganization}/no-trailing-slash-in-links`]: true,
    },
    customRules: [noTrailingSlashInLinksRule],
  });

  expect(page.map(({ lineNumber, errorDetail }) => `${String(lineNumber)}: ${errorDetail ?? ''}`)).toStrictEqual([
    '3: ./docs/',
    '3: ./docs/',
    '5: ./images/',
    '5: https://example.com/a/',
    '7: https://example.com/b/',
    '7: www.example.com/c/',
    '17: https://example.com/f/',
  ]);

  expect(applyFixes(PAGE, page).split('\n')).toStrictEqual([
    '# Page',
    '',
    'A [docs](./docs "Docs") link and a [`docs/`](<./docs>) one.',
    '',
    'An ![image](./images) and an <https://example.com/a> autolink.',
    '',
    'A bare https://example.com/b and www.example.com/c in prose.',
    '',
    'A [root](/) link, a [query](https://example.com/d/?e=1) and a [mail](<mailto:acme@example.com>) one.',
    '',
    'A `[code](./code/)` span and a [reference][r].',
    '',
    '```md',
    '[fence](./fence/)',
    '```',
    '',
    '[r]: https://example.com/f',
    '',
  ]);
});
