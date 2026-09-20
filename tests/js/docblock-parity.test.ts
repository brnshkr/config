import { expect, test } from 'vitest';

import { TAG_SEQUENCE } from '../../src/js/eslint/configs/jsdoc';

import { extractPhpRuleOptionGroups, extractPhpRuleOptionValues, readPhpSource } from './utils/php-source';

const FALSE_FRIENDS = new Set(['type']);
const MINIMUM_SHARED_TAGS = 20;
const phpSource = readPhpSource('PhpCsFixer.php');
const phpOrder = extractPhpRuleOptionValues(phpSource, 'phpdoc_order', 'order');
const phpGroups = extractPhpRuleOptionGroups(phpSource, 'phpdoc_separation', 'groups');
const javascriptGroups = TAG_SEQUENCE.map(({ tags }) => tags);
const javascriptOrder = javascriptGroups.flat();

const sharedTags = new Set(
  javascriptOrder.filter((tag) => phpOrder.includes(tag) && !FALSE_FRIENDS.has(tag)),
);

const keepShared = (tags: string[]): string[] => tags.filter((tag) => sharedTags.has(tag));

const keepSharedGroups = (groups: string[][]): string[][] => groups
  .map((tags) => keepShared(tags))
  .filter((tags) => tags.length > 0);

const stripTwinPrefix = (tag: string): string => tag.replace(/^(?:phpstan|psalm)-/v, '');

test('both stacks order the tags they share the same way', () => {
  expect(sharedTags.size).toBeGreaterThan(MINIMUM_SHARED_TAGS);
  expect(keepShared(phpOrder)).toStrictEqual(keepShared(javascriptOrder));
});

test('both stacks group the tags they share the same way', () => {
  expect(keepSharedGroups(phpGroups)).toStrictEqual(keepSharedGroups(javascriptGroups));
});

test('the php groups and the php order agree', () => {
  const groupedTags = [...new Set(phpGroups.flat().map((tag) => stripTwinPrefix(tag)))];

  expect(groupedTags).toStrictEqual(phpOrder.filter((tag) => groupedTags.includes(tag)));
});
