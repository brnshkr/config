/**
 * @internal @brnshkr/config/commitlint
 */

import { ERROR } from '../utils/constants';
import { MODULES, resolvePackages } from '../utils/module';

import type { RuleOutcome } from '@commitlint/types';
import type { TenseOptions } from 'commitlint-plugin-tense/dist/library/ensure-tense';
import type { Config } from '../types/config';

const ALLOWLIST = <const>[
  'announce',
  'called',
  'console',
  'cover',
  'covers',
  'decide',
  'dismantle',
  'excludes',
  'harden',
  'hoist',
  'measure',
  'missing',
  'non-empty-string',
  'pad',
  'promote',
  'prove',
  'spell',
  'throw',
  'widen',
];

export const tense = (options?: Partial<TenseOptions>): Config[] => {
  const {
    requiredAll: [commitlintPluginTense],
  } = resolvePackages(MODULES.tense);

  const subjectTenseRule = commitlintPluginTense?.rules['tense/subject-tense'];

  if (!subjectTenseRule) {
    return [];
  }

  const {
    allowlist = [],
    ...tenseOptions
  } = options ?? {};

  return [
    {
      plugins: [
        {
          rules: {
            'tense/subject-tense': async (parsedCommit, ruleCondition, ruleOptions): Promise<RuleOutcome> => {
              const commitWithFirstSubjectWord = structuredClone(parsedCommit);

              commitWithFirstSubjectWord['subject'] = parsedCommit['subject']?.split(/\s+/v, 1)[0] ?? '';

              return subjectTenseRule(commitWithFirstSubjectWord, ruleCondition, ruleOptions);
            },
          },
        },
      ],
      rules: {
        'tense/subject-tense': [ERROR, 'always', {
          allowedTenses: ['present-imperative'],
          firstOnly: true,
          ...tenseOptions,
          allowlist: [
            ...ALLOWLIST,
            ...allowlist,
          ],
        }],
      },
    },
  ];
};
