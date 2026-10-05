import { lint } from 'markdownlint/sync';
import { expect, test } from 'vitest';

import { noWarningCommentsRule } from '#markdownlint/configs/builtin/no-warning-comments.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

const PAGE = [
  '# Page',
  '',
  'A `TODO` in inline code names the term.',
  '',
  '<!-- TODO: a comment of its own -->',
  '',
  'Prose with a FIXME and <!-- HACK inline --> comment.',
  '',
  '<!--',
  'XXX on a later line',
  '-->',
  '',
  '```sh',
  '# TODO in a fence',
  '```',
  '',
].join('\n');

test('noWarningCommentsRule reports every term outside inline code on the line it stands on', () => {
  const { page = [] } = lint({
    strings: { page: PAGE },
    config: {
      default: false,
      [`${packageOrganization}/no-warning-comments`]: true,
    },
    customRules: [noWarningCommentsRule],
  });

  expect(page.map(({ lineNumber, errorDetail }) => `${String(lineNumber)}: ${errorDetail ?? ''}`)).toStrictEqual([
    '5: TODO',
    '7: FIXME',
    '7: HACK',
    '10: XXX',
    '14: TODO',
  ]);
});
