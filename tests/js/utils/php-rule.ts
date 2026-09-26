import path from 'node:path';

import { createPattern } from '../../../src/js/shared/utils/pattern';

import { readText } from './filesystem';

const PHP_RULE_ROOT = path.resolve(import.meta.dirname, '../../../src/php/PhpStan/Rule');

export const readPhpRuleSource = (relativePath: string): string => readText(path.join(PHP_RULE_ROOT, relativePath));

export const extractPhpListConstant = (source: string, constantName: string): string[] => {
  const entries = createPattern('v')`const array ${constantName} = \[(?<entries>[^\]]*)\]`
    .exec(source)
    ?.groups?.['entries'] ?? '';

  return entries
    .matchAll(/'(?<entry>[^']+)'/gv)
    .map((match) => match.groups?.['entry'] ?? '')
    .toArray();
};

export const extractPhpStringConstants = (source: string, namePrefix: string): string[] => source
  .matchAll(createPattern('gv')`const string ${namePrefix}\w+\s*=\s*'(?<value>[^']*)'`)
  .map((match) => match.groups?.['value'] ?? '')
  .toArray();
