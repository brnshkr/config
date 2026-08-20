import fs from 'node:fs';
import path from 'node:path';

import { expect, test } from 'vitest';

import { traverseDirectory } from './utils/filesystem';

const REPOSITORY_ROOT = path.resolve(import.meta.dirname, '../..');

const SCANNED_DIRECTORIES = <const>[
  'conf',
  'docs',
  'scripts',
  'src',
  'tests',
];

const SCANNED_ROOT_FILES = <const>[
  'AGENTS.md',
  'CLAUDE.md',
  'Makefile',
  'README.md',
];

const SCANNED_EXTENSIONS = new Set([
  '.js',
  '.md',
  '.mjs',
  '.mk',
  '.php',
  '.ts',
]);

const SKIPPED_SEGMENTS = new Set([
  '__snapshots__',
  '.cache',
  'coverage',
  'dist',
  'node_modules',
  'vendor',
]);

const SKIPPED_PATHS = new Set([
  'src/js/eslint/types/declarations/typegen.d.ts',
  'tests/js/spelling.test.ts',
]);

const BRITISH_SPELLINGS = <const>{
  acknowledgement: 'acknowledgment',
  ageing: 'aging',
  amongst: 'among',
  analogue: 'analog',
  artefact: 'artifact',
  behaviour: 'behavior',
  cancelled: 'canceled',
  catalogue: 'catalog',
  centre: 'center',
  cheque: 'check',
  colour: 'color',
  cosy: 'cozy',
  defence: 'defense',
  enquire: 'inquire',
  enrolment: 'enrollment',
  favour: 'favor',
  fibre: 'fiber',
  flavour: 'flavor',
  fulfil: 'fulfill',
  grey: 'gray',
  honour: 'honor',
  humour: 'humor',
  instalment: 'installment',
  judgement: 'judgment',
  kerb: 'curb',
  labelled: 'labeled',
  labour: 'labor',
  learnt: 'learned',
  licence: 'license',
  litre: 'liter',
  marvellous: 'marvelous',
  metre: 'meter',
  modelling: 'modeling',
  mould: 'mold',
  moustache: 'mustache',
  neighbour: 'neighbor',
  offence: 'offense',
  plough: 'plow',
  practise: 'practice',
  pretence: 'pretense',
  programme: 'program',
  pyjamas: 'pajamas',
  sceptic: 'skeptic',
  signalled: 'signaled',
  skilful: 'skillful',
  smoulder: 'smolder',
  spelt: 'spelled',
  storey: 'story',
  sulphur: 'sulfur',
  theatre: 'theater',
  tonne: 'ton',
  travelled: 'traveled',
  travelling: 'traveling',
  tyre: 'tire',
  vapour: 'vapor',
  whilst: 'while',
};

const BRITISH_S_STEMS = <const>[
  'analys',
  'categoris',
  'characteris',
  'customis',
  'emphasis',
  'initialis',
  'maximis',
  'minimis',
  'normalis',
  'optimis',
  'organis',
  'prioritis',
  'recognis',
  'serialis',
  'standardis',
  'summaris',
  'utilis',
];

const S_STEM_SUFFIX = String.raw`(?:e|es|ed|ing|ation|ations)`;

const ALLOWED_PATHS = new Map([
  ['src/php/PhpStan.php', new Set(['analyse', 'analyseandscan'])],
  ['tests/php/PhpStan/Rule/PublicApiDocumentationRuleTest.php', new Set(['analyse'])],
]);

const ALLOWED_IDENTIFIERS = <const>[
  'analyseAndScan',
  'phpstan-analyse',
];

interface SpellingPattern {
  pattern: RegExp;
  toAmerican: (word: string) => string;
}

const collectFilePaths = (): string[] => {
  const filePaths: string[] = [...SCANNED_ROOT_FILES];

  const onEncounterFile = (filePath: string): void => {
    const relativePath = path.relative(REPOSITORY_ROOT, filePath).replaceAll(path.sep, '/');

    if (SKIPPED_PATHS.has(relativePath)
      || relativePath.split('/').some((segment) => SKIPPED_SEGMENTS.has(segment))) {
      return;
    }

    if (SCANNED_EXTENSIONS.has(path.extname(filePath)) || path.basename(filePath) === 'Makefile') {
      filePaths.push(relativePath);
    }
  };

  for (const directory of SCANNED_DIRECTORIES) {
    traverseDirectory(path.join(REPOSITORY_ROOT, directory), onEncounterFile);
  }

  return filePaths;
};

const buildPatterns = (): SpellingPattern[] => [
  ...Object.entries(BRITISH_SPELLINGS).map(([british, american]): SpellingPattern => ({
    pattern: new RegExp(String.raw`\b${british}\w*`, 'giv'),
    toAmerican: () => american,
  })),
  ...BRITISH_S_STEMS.map((stem): SpellingPattern => ({
    pattern: new RegExp(String.raw`\b${stem}${S_STEM_SUFFIX}\b`, 'giv'),
    toAmerican: (word) => `${stem.slice(0, -1)}z${word.slice(stem.length)}`,
  })),
];

const hasAllowedIdentifierAt = (contents: string, index: number, length: number): boolean => {
  const coversMatch = (identifier: string): boolean => {
    const start = contents.lastIndexOf(identifier, index);

    return start !== -1 && start + identifier.length >= index + length;
  };

  return ALLOWED_IDENTIFIERS.some((identifier) => coversMatch(identifier));
};

test('no british spellings in sources, docs or docblocks', () => {
  const patterns = buildPatterns();
  const findings: string[] = [];

  for (const relativePath of collectFilePaths()) {
    const contents = fs.readFileSync(path.join(REPOSITORY_ROOT, relativePath), 'utf-8');
    const allowedWords = ALLOWED_PATHS.get(relativePath);

    for (const { pattern, toAmerican } of patterns) {
      for (const match of contents.matchAll(pattern)) {
        const [word] = match;

        if (allowedWords?.has(word.toLowerCase()) === true
          || hasAllowedIdentifierAt(contents, match.index, word.length)) {
          continue;
        }

        const line = contents.slice(0, match.index).split('\n').length;

        findings.push(`${relativePath}:${String(line)} — "${word}", use "${toAmerican(word)}"`);
      }
    }
  }

  expect(findings.toSorted()).toStrictEqual([]);
});
