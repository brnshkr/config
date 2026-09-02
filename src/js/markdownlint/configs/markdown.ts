/**
 * @internal @brnshkr/config/markdownlint
 */

import { MAX_LEN } from '../../shared/utils/constants';
import { TABLE_STYLE } from '../utils/constants';

import type { Config } from '../types/config';

/* eslint-disable ts/naming-convention -- Options need to be cased like this */
export const markdown = (): Config[] => [
  {
    config: {
      default: true,
      'code-block-style': {
        style: 'fenced',
      },
      'code-fence-style': {
        style: 'backtick',
      },
      'emphasis-style': {
        style: 'underscore',
      },
      'heading-style': {
        style: 'atx',
      },
      'hr-style': {
        style: '---',
      },
      'line-length': {
        line_length: MAX_LEN,
        code_blocks: false,
        headings: false,
        tables: false,
      },
      'no-duplicate-heading': {
        siblings_only: true,
      },
      'no-inline-html': {
        allowed_elements: [
          'a',
          'br',
          'dd',
          'del',
          'details',
          'div',
          'dl',
          'dt',
          'h1',
          'img',
          'ins',
          'kbd',
          'p',
          'q',
          'rp',
          'rt',
          'ruby',
          'samp',
          'sub',
          'summary',
          'sup',
          'var',
        ],
      },
      'ol-prefix': {
        style: 'ordered',
      },
      'strong-style': {
        style: 'asterisk',
      },
      'table-column-style': {
        style: TABLE_STYLE,
      },
      'table-pipe-style': {
        style: 'leading_and_trailing',
      },
      'ul-style': {
        style: 'dash',
      },
    },
  },
  {
    overrides: [
      {
        filter: [
          '**/.github/PULL_REQUEST_TEMPLATE.md',
          '**/.github/ISSUE_TEMPLATE/**',
          '**/profile/README.md',
          '**/AGENTS.md',
          '**/CLAUDE.md',
        ],
        config: {
          'first-line-heading': {
            allow_preamble: true,
          },
        },
      },
      {
        filter: [
          '**/README.md',
        ],
        config: {
          'heading-sentence-case': false,
        },
      },
    ],
  },
];
/* eslint-enable ts/naming-convention -- Restore rule */
