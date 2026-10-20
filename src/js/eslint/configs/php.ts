/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName } from '#eslint/utils/config.ts';
import { GLOB_PHP_HTML_BLOCKS, GLOB_PHP_REGEXP_BLOCKS } from '#eslint/utils/globs.ts';
import { isModuleEnabled, MODULES, PACKAGES } from '#eslint/utils/module.ts';
import { PHP_INDENT } from '#shared/utils/constants.ts';
import { GLOB_PHP, GLOB_PHP_TEMPLATE_FILES } from '#shared/utils/globs.ts';
import { doAllPackagesExist } from '#shared/utils/module.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';

import type { Linter } from 'eslint';
import type { Config } from '#eslint/types/config.ts';

interface SourcePosition {
  line: number;
  column: number;
}

interface PhpBlock {
  text: string;
  extension: string;
  mapPosition: (line: number, column: number) => SourcePosition;
}

interface PhpBlockKinds {
  hasHtmlBlocks: boolean;
  hasRegexpBlocks: boolean;
}

interface TranslatedPattern {
  source: string;
  flags: string;
  valueOffsets: number[];
}

const REGEXP_BLOCK_PREFIX = 'new RegExp(\'';
const JAVASCRIPT_SYNTAX_CHARACTERS = new Set(String.raw`$()*+./?[\]^{|}`);

const STRING_LITERAL_ESCAPES = new Map([
  ['\'', String.raw`\'`],
  ['\\', String.raw`\\`],
  ['\n', String.raw`\n`],
  ['\r', String.raw`\r`],
]);

const ALL_RULES = '';

const NO_OP_FLAGS = new Set([
  'D',
  'S',
  'u',
  'X',
]);

const createHeredocPattern = (): RegExp => /<<<[\t ]*(?<quote>["']?)HTML\k<quote>\r?\n(?<body>(?:.*\r?\n)*?)(?<indent>[\t ]*)HTML\b/dgv;
const createMarkedStringPattern = (): RegExp => /(?:\/\/[\t ]*language=html\b|\/\*[\t ]*html[\t ]*\*\/|\/\*\*[\t ]*@lang[\t ]+html[\t ]*\*\/)[^"']*'(?<body>(?:[^'\\]|\\.)*)'/dgiv;
const createPhpTokenPattern = (): RegExp => /(?:\/\/|#(?!\[))[^\n]*|\/\*.*?\*\/|<<<[\t ]*(?<quote>["']?)(?<label>[A-Z_a-z]\w*)\k<quote>\r?\n.*?\n[\t ]*\k<label>\b|"(?:[^"\\]|\\.)*"|'(?<single>(?:[^'\\]|\\.)*)'/dgsv;
const createRegexpShapePattern = (): RegExp => /^(?<delimiter>[^\s\w"%'\\`])(?<body>(?:\\.|(?!\k<delimiter>)[^\\])*)\k<delimiter>(?<flags>[ADJSUXimnsux]*)$/sv;
const createInlineFlagsPattern = (): RegExp => /^\(\?(?<flags>[ims]+)\)/v;
const createSingleQuotedTokenPattern = (): RegExp => /\\(?<escaped>['\\])|(?<character>.)/gsv;
const createPcreTokenPattern = (): RegExp => /\\x\{(?<hex>[\dA-Fa-f]+)\}|\\(?<escaped>.)|(?<character>.)/gsv;
const createRangeDirectivePattern = (): RegExp => /^eslint-(?<kind>disable|enable)(?=\s|$)(?<rules>(?:(?!\s--).)*)/sv;
const createNextLineDirectivePattern = (): RegExp => /^\s*(?:\/\/|\/\*)\s*(?<directive>eslint-disable-next-line\b.*)$/v;
const createSameLineDirectivePattern = (): RegExp => /(?:\/\/|\/\*)\s*(?<directive>eslint-disable-line\b.*)$/v;

const getSourcePosition = (text: string, offset: number): SourcePosition => {
  const lines = text.slice(0, offset).split('\n');

  return {
    line: lines.length,
    column: (lines.at(-1)?.length ?? 0) + 1,
  };
};

const createHtmlBlock = (text: string, position: SourcePosition, indent: number): PhpBlock => ({
  text,
  extension: 'html',
  mapPosition: (line, column) => ({
    line: position.line + (line - 1),
    column: column + (line === 1 ? position.column - 1 : indent),
  }),
});

const findPhpHtmlBlocks = (text: string): PhpBlock[] => [
  ...text.matchAll(createHeredocPattern()).map((match) => {
    const indent = match.groups?.['indent'] ?? '';
    const [bodyStart = 0] = match.indices?.groups?.['body'] ?? [];

    return createHtmlBlock(
      (match.groups?.['body'] ?? '')
        .split('\n')
        .map((line) => line.slice(indent.length))
        .join('\n'),
      getSourcePosition(text, bodyStart + indent.length),
      indent.length,
    );
  }),
  ...text.matchAll(createMarkedStringPattern()).map((match) => {
    const [bodyStart = 0] = match.indices?.groups?.['body'] ?? [];

    return createHtmlBlock(`${match.groups?.['body'] ?? ''}\n`, getSourcePosition(text, bodyStart), 0);
  }),
];

const decodeSingleQuoted = (raw: string): { value: string; rawOffsets: number[] } => {
  const rawOffsets: number[] = [];
  let value = '';

  for (const match of raw.matchAll(createSingleQuotedTokenPattern())) {
    const { escaped, character = '' } = match.groups ?? {};

    value += escaped ?? character;
    rawOffsets.push(match.index + Number(escaped !== undefined));
  }

  return {
    value,
    rawOffsets,
  };
};

const translateFlags = (pcreFlags: string, inlineFlags: string): string => [...new Set(`${pcreFlags}${inlineFlags}u`)]
  .filter((flag) => flag === 'u' || !NO_OP_FLAGS.has(flag))
  .toSorted((first, second) => first.localeCompare(second))
  .join('');

const translateEscape = (escaped: string, delimiter: string, isInClass: boolean, flags: string): string => {
  if (escaped === delimiter && (isInClass || delimiter === '/' || !JAVASCRIPT_SYNTAX_CHARACTERS.has(delimiter))) {
    return delimiter;
  }

  if (!isInClass && escaped === 'A') {
    return '^';
  }

  return (!isInClass && escaped === 'z' && !flags.includes('m')) ? '$' : `\\${escaped}`;
};

const translateToken = (
  match: RegExpExecArray,
  pattern: Pick<TranslatedPattern, 'flags'> & { delimiter: string; isInClass: boolean },
): string => {
  const { hex, escaped, character = '' } = match.groups ?? {};

  if (hex !== undefined) {
    return String.raw`\u{${hex}}`;
  }

  return escaped === undefined
    ? character
    : translateEscape(
      escaped,
      pattern.delimiter,
      pattern.isInClass,
      pattern.flags,
    );
};

const isInsideClass = (character: string | undefined, wasInClass: boolean): boolean => character === '['
  || (wasInClass && character !== ']');

const translatePattern = (body: string, delimiter: string, pcreFlags: string): TranslatedPattern => {
  const inlineFlagsMatch = createInlineFlagsPattern().exec(body);
  const inlineLength = inlineFlagsMatch?.[0].length ?? 0;
  const valueOffsets: number[] = [];
  let source = '';
  let isInClass = false;

  for (const match of body.slice(inlineLength).matchAll(createPcreTokenPattern())) {
    const translatedToken = translateToken(match, {
      flags: pcreFlags,
      delimiter,
      isInClass,
    });

    isInClass = isInsideClass(match.groups?.['character'], isInClass);

    for (const translatedCharacter of translatedToken) {
      const encodedCharacter = STRING_LITERAL_ESCAPES.get(translatedCharacter) ?? translatedCharacter;

      source += encodedCharacter;
      valueOffsets.push(...Array.from(encodedCharacter, () => 1 + inlineLength + match.index));
    }
  }

  return {
    source,
    flags: translateFlags(pcreFlags, inlineFlagsMatch?.groups?.['flags'] ?? ''),
    valueOffsets,
  };
};

const stripBlockCommentEnd = (directive: string): string => directive
  .trimEnd()
  .replace(/\*\/$/v, '')
  .trimEnd();

const getDirectiveComments = (lines: string[], lineIndex: number): { above: string; after: string } => {
  const above = createNextLineDirectivePattern().exec(lines.at(lineIndex - 1) ?? '')?.groups?.['directive'];
  const after = createSameLineDirectivePattern().exec(lines.at(lineIndex) ?? '')?.groups?.['directive'];

  return {
    above: above === undefined ? '' : `// ${stripBlockCommentEnd(above)}\n`,
    after: after === undefined ? '' : ` // ${stripBlockCommentEnd(after)}`,
  };
};

const createRegexpBlock = (
  text: string,
  literalStart: number,
  raw: string,
  rangeDirective: string,
): PhpBlock | undefined => {
  const { value, rawOffsets } = decodeSingleQuoted(raw);
  const { delimiter = '', body, flags = '' } = createRegexpShapePattern().exec(value)?.groups ?? {};

  if (body === undefined) {
    return undefined;
  }

  const translatedPattern = translatePattern(body, delimiter, flags);
  const literalPosition = getSourcePosition(text, literalStart);
  const directives = getDirectiveComments(text.split('\n'), literalPosition.line - 1);
  const leadingDirectives = `${rangeDirective}${directives.above}`;
  const codeLine = leadingDirectives.split('\n').length;
  const code = `${REGEXP_BLOCK_PREFIX}${translatedPattern.source}', '${translatedPattern.flags}');`;

  return {
    text: `${leadingDirectives}${code}${directives.after}\n`,
    extension: 'regexp',
    mapPosition: (line, column): SourcePosition => {
      if (line < codeLine) {
        return {
          line: literalPosition.line - 1,
          column,
        };
      }

      const lastSourceIndex = translatedPattern.valueOffsets.length - 1;
      const sourceIndex = Math.min(Math.max(column - 1 - REGEXP_BLOCK_PREFIX.length, 0), lastSourceIndex);
      const valueIndex = translatedPattern.valueOffsets.at(sourceIndex) ?? 0;

      return getSourcePosition(text, literalStart + (rawOffsets.at(valueIndex) ?? 0));
    },
  };
};

const updateDisabledRules = (disabledRules: Set<string>, token: string): void => {
  const directive = createRangeDirectivePattern()
    .exec(stripBlockCommentEnd(token.replace(/^(?:\/\/|\/\*(?!\*))/v, '')).trimStart())
    ?.groups;

  if (directive === undefined) {
    return;
  }

  const ruleNames = (directive['rules'] ?? '')
    .split(',')
    .map((ruleName) => ruleName.trim())
    .filter((ruleName) => ruleName !== '');

  if (directive['kind'] === 'enable' && ruleNames.length === 0) {
    disabledRules.clear();
  }

  const updatedRuleNames = ruleNames.length === 0 ? [ALL_RULES] : ruleNames;

  for (const ruleName of updatedRuleNames) {
    if (directive['kind'] === 'disable') {
      disabledRules.add(ruleName);
    } else {
      disabledRules.delete(ruleName);
    }
  }
};

const getRangeDirective = (disabledRules: Set<string>): string => {
  if (disabledRules.size === 0) {
    return '';
  }

  return disabledRules.has(ALL_RULES) ? '/* eslint-disable */\n' : `/* eslint-disable ${[...disabledRules].join(', ')} */\n`;
};

const findPhpRegexpBlocks = (text: string): PhpBlock[] => {
  const blocks: PhpBlock[] = [];
  const disabledRules = new Set<string>();

  for (const match of text.matchAll(createPhpTokenPattern())) {
    const [literalStart] = match.indices?.groups?.['single'] ?? [];
    const raw = match.groups?.['single'];

    if (literalStart === undefined || raw === undefined) {
      updateDisabledRules(disabledRules, match[0]);
    } else {
      blocks.push(...[
        createRegexpBlock(
          text,
          literalStart,
          raw,
          getRangeDirective(disabledRules),
        ),
      ].filter((block) => block !== undefined));
    }
  }

  return blocks;
};

const mapMessage = (block: PhpBlock, message: Linter.LintMessage): Linter.LintMessage => ({
  ...message,
  ...block.mapPosition(message.line, message.column),
  ...((message.endLine === undefined || message.endColumn === undefined)
    ? undefined
    : {
      endLine: block.mapPosition(message.endLine, message.endColumn).line,
      endColumn: block.mapPosition(message.endLine, message.endColumn).column,
    }),
});

const createPhpProcessor = (blockKinds: PhpBlockKinds): Linter.Processor => {
  const blocksByFilePath = new Map<string, PhpBlock[]>();

  return {
    meta: {
      name: `${packageOrganization}/php`,
    },
    supportsAutofix: false,
    preprocess: (text, filePath): ReturnType<NonNullable<Linter.Processor['preprocess']>> => {
      const blocks = [
        ...(blockKinds.hasHtmlBlocks ? findPhpHtmlBlocks(text) : []),
        ...(blockKinds.hasRegexpBlocks ? findPhpRegexpBlocks(text) : []),
      ];

      blocksByFilePath.set(filePath, blocks);

      return blocks.map((block, index) => ({
        text: block.text,
        filename: `${String(index)}.${block.extension}`,
      }));
    },
    postprocess: (blockMessages, filePath): Linter.LintMessage[] => {
      const blocks = blocksByFilePath.get(filePath) ?? [];

      blocksByFilePath.delete(filePath);

      return blockMessages.flatMap((messages, index) => {
        const block = blocks.at(index);

        return block === undefined ? [] : messages.map((message) => mapMessage(block, message));
      });
    },
  };
};

export const php = (): Config[] => {
  const hasHtmlBlocks = isModuleEnabled(MODULES.html) && doAllPackagesExist([PACKAGES.HTML_ESLINT_PLUGIN]);
  const hasRegexpBlocks = isModuleEnabled(MODULES.regexp) && doAllPackagesExist([PACKAGES.ESLINT_PLUGIN_REGEXP]);

  return [
    {
      name: buildConfigName(MAIN_SCOPES.PHP, SUB_SCOPES.PROCESSOR),
      files: [
        GLOB_PHP,
      ],
      ignores: GLOB_PHP_TEMPLATE_FILES,
      processor: createPhpProcessor({
        hasHtmlBlocks,
        hasRegexpBlocks,
      }),
    },
    ...(hasHtmlBlocks
      ? [{
        name: buildConfigName(MAIN_SCOPES.PHP, `${SUB_SCOPES.RULES}-html`),
        files: [
          GLOB_PHP_HTML_BLOCKS,
        ],
        rules: {
          'html/indent': ['error', PHP_INDENT],
        },
      } satisfies Config]
      : []),
    ...(hasRegexpBlocks
      ? [{
        name: buildConfigName(MAIN_SCOPES.PHP, `${SUB_SCOPES.RULES}-regexp`),
        files: [
          GLOB_PHP_REGEXP_BLOCKS,
        ],
        linterOptions: {
          reportUnusedDisableDirectives: 'off',
        },
        rules: {
          'prefer-regex-literals': 'off',
          'regexp/control-character-escape': 'off',
          'regexp/grapheme-string-literal': 'off',
          'regexp/prefer-set-operation': 'off',
          'regexp/require-unicode-sets-regexp': 'off',
          'regexp/simplify-set-operations': 'off',
          'regexp/unicode-escape': 'off',
          'regexp/unicode-property': ['error', {
            key: 'short',
            property: 'short',
          }],
        },
      } satisfies Config]
      : []),
  ];
};
