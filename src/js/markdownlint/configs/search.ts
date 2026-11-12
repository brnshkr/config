/**
 * @internal @brnshkr/config/markdownlint
 */

import { resolveCustomRule } from '#markdownlint/utils/config.ts';
import { MODULES, PACKAGES, resolvePackages } from '#markdownlint/utils/module.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';
import { createPattern } from '#shared/utils/pattern.ts';
import { TERMS } from '#shared/utils/terms.ts';

import type { Config } from '#markdownlint/types/config.ts';
import type { SearchReplaceRule } from '#markdownlint/types/rules.ts';

const SEPARATOR_PATTERNS = new Map([
  ['-', '-?'],
  ['.', String.raw`\.?`],
  [' ', ' ?'],
]);

const RULES = <const>[
  {
    name: 'ellipsis',
    message: 'Use an ellipsis rather than three dots.',
    searchPattern: String.raw`/(?<!\]\([^)]*)\.\.\./gu`,
    replace: '…',
    searchScope: 'text',
  },
  {
    name: 'em-dash',
    message: 'Use an em dash rather than two hyphens.',
    searchPattern: '/(?<= )--(?= )/gu',
    replace: '—',
    searchScope: 'text',
  },
  {
    name: 'https',
    message: 'Link over `https`.',
    searchPattern: String.raw`/http:\/\/(?!(?:www\.)?w3\.org\/|localhost|127\.0\.0\.1)/gu`,
    replace: 'https://',
    searchScope: 'text',
  },
  {
    name: 'no-non-breaking-space',
    message: 'Use a regular space.',
    searchPattern: String.raw`/\u00A0/gu`,
    replace: ' ',
    searchScope: 'all',
  },
  {
    name: 'no-zero-width-characters',
    message: 'Remove the zero-width character.',
    searchPattern: String.raw`/[\u200B-\u200D\uFEFF]/gu`,
    replace: '',
    searchScope: 'all',
  },
  {
    name: 'relative-link-prefix',
    message: 'Prefix a relative link with `./`.',
    searchPattern: String.raw`/\]\((?![.#/]|[a-z][a-z0-9+.-]*:)/gu`,
    replace: '](./',
    searchScope: 'text',
  },
  {
    name: 'scoped-package-names',
    message: 'Name an organization package by its scope.',
    searchPattern: String(createPattern('gu')`(?<![\w/@])${packageOrganization}/(?=[a-z])`),
    replace: `@${packageOrganization}/`,
    searchScope: 'text',
  },
  {
    name: 'straight-double-quotes',
    message: 'Use straight double quotes.',
    searchPattern: '/[“”]/gu',
    replace: '"',
    searchScope: 'text',
  },
  {
    name: 'straight-single-quotes',
    message: 'Use straight single quotes.',
    searchPattern: '/[‘’]/gu',
    replace: '\'',
    searchScope: 'text',
  },
  {
    name: 'tool-names',
    message: 'Spell a name the way its own project spells it.',
    searchPattern: TERMS.map((term) => {
      const exactPatterns = [
        term,
        term.toLowerCase(),
      ].map((spelling) => spelling.replaceAll('.', String.raw`\.`));

      const anyCasingPattern = term.replaceAll(
        /[\p{Letter} \-.]/gv,
        (character) => SEPARATOR_PATTERNS.get(character) ?? `[${character.toUpperCase()}${character.toLowerCase()}]`,
      );

      return String.raw`/(?<![\w./@-])(?!(?:${exactPatterns.join('|')})(?![\w-]))${anyCasingPattern}(?![\w-])/gu`;
    }),
    replace: [...TERMS],
    searchScope: 'text',
  },
] satisfies SearchReplaceRule[];

export const search = (): Config[] => {
  const {
    requiredAll: [isMarkdownlintRuleSearchReplaceInstalled],
  } = resolvePackages(MODULES.search);

  if (!isMarkdownlintRuleSearchReplaceInstalled) {
    return [];
  }

  return [
    {
      customRules: [
        resolveCustomRule(PACKAGES.MARKDOWNLINT_RULE_SEARCH_REPLACE),
      ],
      config: {
        'search-replace': {
          rules: RULES,
        },
      },
    },
  ];
};
