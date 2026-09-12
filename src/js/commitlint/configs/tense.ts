/**
 * @internal @brnshkr/config/commitlint
 */

import { ERROR } from '../utils/constants';
import { MODULES, PACKAGES, resolvePackages } from '../utils/module';

import type { TenseOptions } from 'commitlint-plugin-tense/dist/library/ensure-tense';
import type { Config } from '../types/config';

const ALLOWLIST = <const>[
  'called',
  'console',
  'cover',
  'covers',
  'decide',
  'dismantle',
  'excludes',
  'harden',
  'hoist',
  'missing',
  'non-empty-string',
  'promote',
  'spell',
  'throw',
  'widen',
];

export const tense = (options?: Partial<TenseOptions>): Config[] => {
  const {
    requiredAll: [isCommitlintPluginTenseInstalled],
  } = resolvePackages(MODULES.tense);

  if (!isCommitlintPluginTenseInstalled) {
    return [];
  }

  const {
    allowlist = [],
    ...tenseOptions
  } = options ?? {};

  return [
    {
      plugins: [
        PACKAGES.COMMITLINT_PLUGIN_TENSE,
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
