import fs from 'node:fs';
import path from 'node:path';

const PHP_SOURCE_ROOT = path.resolve(import.meta.dirname, '../../../src/php');

const findArrayBody = (source: string, keyName: string): string => {
  const opening = new RegExp(String.raw`'${keyName}'\s*=>\s*\[`, 'v').exec(source);

  if (!opening) {
    return '';
  }

  const start = opening.index + opening[0].length;
  let depth = 0;

  for (let index = start - 1; index < source.length; index += 1) {
    if (source[index] === '[') {
      depth += 1;
    }

    if (source[index] === ']') {
      depth -= 1;

      if (depth === 0) {
        return source.slice(start, index);
      }
    }
  }

  return '';
};

const splitNestedArrays = (body: string): string[] => {
  const nested: string[] = [];
  let depth = 0;
  let start = 0;

  for (let index = 0; index < body.length; index += 1) {
    if (body[index] === '[') {
      if (depth === 0) {
        start = index + 1;
      }

      depth += 1;
    }

    if (body[index] === ']') {
      depth -= 1;

      if (depth === 0) {
        nested.push(body.slice(start, index));
      }
    }
  }

  return nested;
};

const extractQuotedValues = (body: string): string[] => body
  .matchAll(/'(?<value>[^']+)'/gv)
  .map((match) => match.groups?.['value'] ?? '')
  .toArray();

export const readPhpSource = (relativePath: string): string => fs.readFileSync(
  path.join(PHP_SOURCE_ROOT, relativePath),
  'utf-8',
);

export const extractPhpRuleOptionValues = (
  source: string,
  ruleName: string,
  optionName: string,
): string[] => extractQuotedValues(findArrayBody(findArrayBody(source, ruleName), optionName));

export const extractPhpRuleOptionGroups = (
  source: string,
  ruleName: string,
  optionName: string,
): string[][] => splitNestedArrays(findArrayBody(findArrayBody(source, ruleName), optionName))
  .map((group) => extractQuotedValues(group));
