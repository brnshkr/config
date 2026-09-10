import fs from 'node:fs';
import path from 'node:path';

import { expect, test } from 'vitest';

import { createPhpRegexes } from './fixtures/php/regexes';
import { traverseDirectory } from './utils/filesystem';

const REPOSITORY_ROOT = path.resolve(import.meta.dirname, '../..');

const SCANNED_DIRECTORIES = <const>[
  'conf/ai/mate/src',
  'src/php',
  'tests/php',
];

const CONSUMER_CALLS = <const>[
  'assertDoesNotMatchRegularExpression(',
  'assertMatchesRegularExpression(',
  '->match(',
  '->replaceMatches(',
  'preg_grep(',
  'preg_match(',
  'preg_match_all(',
  'preg_replace(',
  'preg_replace_callback(',
  'preg_split(',
  'sprintf(',
  'Str::match(',
  'Str::matchAll(',
  'Str::replaceMatches(',
];

const EXCLUDED_PATTERNS = new Set([
  '/%s/',
]);

const CALL_WINDOW_LINES = 3;
const createRegexShapePattern = (): RegExp => /^(?<delimiter>[#\/~]).*\k<delimiter>[a-z]*$/sv;
const createSingleQuotedPattern = (): RegExp => /'(?<value>(?:[^'\\]|\\.)*)'/gv;

const collectPhpFiles = (): string[] => {
  const filePaths: string[] = [];

  for (const directory of SCANNED_DIRECTORIES) {
    traverseDirectory(path.join(REPOSITORY_ROOT, directory), (filePath) => {
      if (path.extname(filePath) === '.php') {
        filePaths.push(filePath);
      }
    });
  }

  return filePaths;
};

const decodeSingleQuoted = (raw: string): string => raw
  .replaceAll(String.raw`\\`, '\\')
  .replaceAll(String.raw`\'`, '\'');

const collectLiterals = (contents: string): Set<string> => new Set(
  contents
    .split('\n')
    .flatMap((line) => [...line.matchAll(createSingleQuotedPattern())])
    .map((match) => decodeSingleQuoted(match.groups?.['value'] ?? '')),
);

const collectWindowPatterns = (window: string): string[] => [...window.matchAll(createSingleQuotedPattern())]
  .map((match) => decodeSingleQuoted(match.groups?.['value'] ?? ''))
  .filter((value) => createRegexShapePattern().test(value) && !EXCLUDED_PATTERNS.has(value));

const collectConsumedPatterns = (): Set<string> => new Set(
  collectPhpFiles().flatMap((filePath) => {
    const lines = fs.readFileSync(filePath, 'utf-8').split('\n');

    return lines.flatMap((line, index) => (CONSUMER_CALLS.some((call) => line.includes(call))
      ? collectWindowPatterns(lines.slice(index, index + CALL_WINDOW_LINES).join('\n'))
      : []));
  }),
);

test('every fixture pattern still exists in its php source', () => {
  const missing = createPhpRegexes().filter(({ file, php }) => {
    const contents = fs.readFileSync(path.join(REPOSITORY_ROOT, file), 'utf-8');

    return !collectLiterals(contents).has(php);
  });

  expect(missing.map(({ file, php }) => `${file} — ${php}`)).toStrictEqual([]);
});

test('every php regex use site is covered by the fixture', () => {
  const covered = new Set(createPhpRegexes().map(({ php }) => php));
  const uncovered = [...collectConsumedPatterns()].filter((pattern) => !covered.has(pattern));

  expect(uncovered.toSorted()).toStrictEqual([]);
});
