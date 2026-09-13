import fs from 'node:fs';
import path from 'node:path';

const PHP_RULE_ROOT = path.resolve(import.meta.dirname, '../../../src/php/PhpStan/Rule');

export const readPhpRuleSource = (relativePath: string): string => fs.readFileSync(
  path.join(PHP_RULE_ROOT, relativePath),
  'utf-8',
);

export const extractPhpListConstant = (source: string, constantName: string): string[] => {
  const entries = new RegExp(String.raw`const array ${constantName} = \[(?<entries>[^\]]*)\]`, 'v')
    .exec(source)
    ?.groups?.['entries'] ?? '';

  return entries
    .matchAll(/'(?<entry>[^']+)'/gv)
    .map((match) => match.groups?.['entry'] ?? '')
    .toArray();
};

export const extractPhpStringConstants = (source: string, namePrefix: string): string[] => source
  .matchAll(new RegExp(String.raw`const string ${namePrefix}\w+\s*=\s*'(?<value>[^']*)'`, 'gv'))
  .map((match) => match.groups?.['value'] ?? '')
  .toArray();
