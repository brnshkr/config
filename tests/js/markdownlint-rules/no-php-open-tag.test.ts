import { lint } from 'markdownlint/sync';
import { expect, test } from 'vitest';

import { noPhpOpenTagRule } from '#markdownlint/configs/builtin/no-php-open-tag.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

const PAGE = [
  '# Page',
  '',
  'Prose naming `<?php` in inline code.',
  '',
  'A short echo tag <?= $name ?> in prose.',
  '',
  '```php',
  '<?php',
  '',
  'declare(strict_types=1);',
  '',
  'return [];',
  '```',
  '',
  '```html',
  '<p><?php echo 1; ?></p>',
  '```',
  '',
  '```js',
  '// declare(strict_types=1) belongs to no PHP here',
  '```',
  '',
  '```PHP title="Config"',
  'declare(strict_types=1);',
  '```',
  '',
  '```xml',
  '<?xml version="1.0"?>',
  '```',
  '',
  'A bare <? echo 1; ?> short tag.',
  '',
  'Closing ?> tags alone keep the page in HTML.',
  '',
].join('\n');

test('noPhpOpenTagRule reports every open tag, short ones included, and strict-types declaration on its line', () => {
  const { page = [] } = lint({
    strings: { page: PAGE },
    config: {
      default: false,
      [`${packageOrganization}/no-php-open-tag`]: true,
    },
    customRules: [noPhpOpenTagRule],
  });

  expect(page.map(({ lineNumber, errorDetail }) => `${String(lineNumber)}: ${errorDetail ?? ''}`)).toStrictEqual([
    '3: <?php',
    '5: <?=',
    '8: <?php',
    '10: declare(strict_types=1);',
    '16: <?php',
    '24: declare(strict_types=1);',
    '28: <?xml',
    '31: <?',
  ]);
});
