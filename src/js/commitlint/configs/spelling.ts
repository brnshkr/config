/**
 * @internal @brnshkr/config/commitlint
 */

import { ERROR } from '#commitlint/utils/constants.ts';
import { getConfigDirectory } from '#shared/utils/project-directory.ts';
import { findInFile } from '#spelling/utils/findings.ts';
import { buildPatterns } from '#spelling/utils/patterns.ts';
import { readAllowlist, readSettings } from '#spelling/utils/settings.ts';

import type { RuleOutcome, SyncRule } from '@commitlint/types';
import type { Config } from '#commitlint/types/config.ts';

const COMMIT_MESSAGE_PATH = '.git/COMMIT_EDITMSG';

/**
 * @throws {Error}
 */
const checkSpelling: SyncRule = (parsedCommit): RuleOutcome => {
  const settings = readSettings(process.cwd(), `${getConfigDirectory()}/spelling.json`);

  const message = [
    parsedCommit.header,
    parsedCommit.body,
    parsedCommit.footer,
  ].filter((part) => typeof part === 'string').join('\n');

  const findings = findInFile(
    message,
    COMMIT_MESSAGE_PATH,
    buildPatterns(settings),
    readAllowlist(settings),
  );

  const britishSpellings = findings.map((finding) => `"${finding.word}", use "${finding.suggestion}"`).join('; ');

  return [
    findings.length === 0,
    `message must be in American English. British spellings: ${britishSpellings}`,
  ];
};

export const spelling = (): Config[] => [
  {
    plugins: [
      {
        rules: {
          'brnshkr/spelling': checkSpelling,
        },
      },
    ],
    rules: {
      'brnshkr/spelling': [
        ERROR,
        'always',
      ],
    },
  },
];
