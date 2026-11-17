/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { buildConfigName } from '#eslint/utils/config.ts';
import { GLOB_HTML_TWIG, GLOB_TWIG_CLASS_BLOCKS } from '#eslint/utils/globs.ts';
import { isModuleEnabled, MODULES } from '#eslint/utils/module.ts';
import { compileConfiguredPattern } from '#shared/utils/configured-pattern.ts';
import { INDENT, MAX_LEN } from '#shared/utils/constants.ts';
import { createPattern } from '#shared/utils/pattern.ts';

import type { Linter, Rule } from 'eslint';
import type { Config } from '#eslint/types/config.ts';
import type { TwigFunction } from '#eslint/types/options.ts';
import type { Maybe } from '#shared/types/core.ts';

interface Token {
  start: number;
  end: number;
  value: string;
}

interface Group {
  open: Token;
  close: Token;
  units: Unit[];
}

type Unit = Group | Token;

interface Branch {
  units: Unit[];
  operator: Maybe<Token>;
}

interface Replacement {
  start: number;
  text: string;
}

interface Translation {
  text: string;
  functionNames: string[];
  translate: (translation: Translation, units: Unit[]) => Replacement[];
}

interface Call {
  name: Token;
  arguments: Group;
}

export const TWIG_PROCESSOR = 'twig';

const BLOCK_EXTENSION = 'classes.js';
const PLACEHOLDER = '_';
const WRAPPING_RULE = 'tailwind/enforce-consistent-line-wrapping';
const OPENING_BRACKETS = new Set(['(', '[', '{']);
const CLOSING_BRACKETS = new Set([')', ']', '}']);
const KEY_SEPARATORS = new Set([':', '=']);

const BRANCH_OPERATORS = new Set([
  ':',
  '?',
  '?:',
  '??',
]);

const CLASS_KEY_MATCH = <const>[
  {
    type: 'objectValues',
    path: '^class$',
  },
];

const VARIANT_MATCH = <const>[
  {
    type: 'objectValues',
    path: '^base$',
  },
  {
    type: 'objectValues',
    path: '^variants.*$',
  },
  {
    type: 'objectValues',
    path: String.raw`^compoundVariants\[\d+\]\.class$`,
  },
];

const DEFAULT_FUNCTIONS = <const>[
  {
    name: 'attrs',
    match: CLASS_KEY_MATCH,
  },
  {
    name: 'attributes.defaults',
    match: CLASS_KEY_MATCH,
  },
  {
    name: 'html_classes',
    match: [
      {
        type: 'strings',
      },
      {
        type: 'objectKeys',
      },
    ],
  },
  {
    name: 'cva',
    match: VARIANT_MATCH,
  },
  {
    name: 'html_cva',
    match: VARIANT_MATCH,
  },
] satisfies readonly TwigFunction[];

const createTokenPattern = (): RegExp => /'(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*"|\?[:?]|[!<=>]=|[\w.]+|[^\s\w]/gv;
const getVirtualName = (name: string): string => name.replaceAll('.', '_');
const isToken = (unit: Maybe<Unit>): unit is Token => unit !== undefined && 'value' in unit;

const isGroup = (unit: Maybe<Unit>, opening: string): unit is Group => unit !== undefined
  && !isToken(unit)
  && unit.open.value === opening;

const isComma = (token: Token): boolean => token.value === ',';
const isBranchOperator = (token: Token): boolean => BRANCH_OPERATORS.has(token.value);

const isClassString = (unit: Unit): boolean => isToken(unit)
  && /^["']/v.test(unit.value)
  && !/#\{|\$\{|`/v.test(unit.value);

const findGroup = (text: string, from: number): Maybe<Group> => {
  const pattern = createTokenPattern();
  const openGroups: Omit<Group, 'close'>[] = [];

  pattern.lastIndex = from;

  for (const match of text.matchAll(pattern)) {
    const token = {
      start: match.index,
      end: match.index + match[0].length,
      value: match[0],
    };

    const current = openGroups.at(-1);

    if (OPENING_BRACKETS.has(token.value)) {
      openGroups.push({
        open: token,
        units: [],
      });
    } else if (current === undefined) {
      return undefined;
    } else if (CLOSING_BRACKETS.has(token.value)) {
      openGroups.pop();

      const group = {
        ...current,
        close: token,
      };

      const parent = openGroups.at(-1);

      if (parent === undefined) {
        return group;
      }

      parent.units.push(group);
    } else {
      current.units.push(token);
    }
  }

  return undefined;
};

const splitUnits = (units: Unit[], isSeparator: (token: Token) => boolean): Unit[][] => {
  let part: Unit[] = [];
  const parts = [part];

  for (const unit of units) {
    if (isToken(unit) && isSeparator(unit)) {
      part = [];
      parts.push(part);
    } else {
      part.push(unit);
    }
  }

  return parts;
};

const getBranches = (units: Unit[]): Branch[] => {
  const operators = units.filter((unit): unit is Token => isToken(unit) && isBranchOperator(unit));

  return splitUnits(units, isBranchOperator).map((branchUnits, index) => ({
    units: branchUnits,
    operator: operators.at(index),
  }));
};

const isBalanced = (operators: Token[]): boolean => {
  let openConditions = 0;

  for (const operator of operators) {
    if (operator.value === '?') {
      openConditions += 1;
    } else if (operator.value === ':') {
      if (openConditions === 0) {
        return false;
      }

      openConditions -= 1;
    }
  }

  return openConditions === 0;
};

const findWritablePosition = (text: string, from: number): number => from + Math.max(0, text.slice(from).search(/[^\n\r]/v));

const getEnd = (replacements: Replacement[]): number => Math.max(
  ...replacements.map((replacement) => replacement.start + replacement.text.length),
);

const getTokenReplacements = (tokens: Token[]): Replacement[] => tokens.map((token) => ({
  start: token.start,
  text: token.value,
}));

const getCommaReplacements = (group: Group): Replacement[] => getTokenReplacements(group.units.filter(
  (unit): unit is Token => isToken(unit) && isComma(unit),
));

const getPlaceholderReplacements = (units: Unit[]): Replacement[] => {
  const [first] = units;

  if (first === undefined) {
    return [];
  }

  return [
    {
      start: isToken(first)
        ? first.start
        : first.open.start,
      text: PLACEHOLDER,
    },
  ];
};

const findCall = (translation: Translation, units: Unit[]): Maybe<Call> => {
  const [name, argumentGroup, ...rest] = units;

  return (rest.length === 0 && isToken(name) && translation.functionNames.includes(name.value) && isGroup(argumentGroup, '('))
    ? {
      name,
      arguments: argumentGroup,
    }
    : undefined;
};

const isValue = (translation: Translation, branch: Branch): boolean => {
  const [first, ...rest] = branch.units;

  return branch.operator?.value !== '?'
    && ((first !== undefined && rest.length === 0 && (!isToken(first) || isClassString(first)))
      || findCall(translation, branch.units) !== undefined);
};

const isNamedArgument = (units: Unit[]): boolean => {
  const [name, separator] = units;

  return isToken(name)
    && /^[A-Z_a-z]\w*$/v.test(name.value)
    && isToken(separator)
    && KEY_SEPARATORS.has(separator.value);
};

const translateExpression = (translation: Translation, units: Unit[]): Replacement[] => {
  const branches = getBranches(units);
  const operators = branches.flatMap((branch) => branch.operator ?? []);

  if (isBalanced(operators) && branches.every((branch) => branch.units.length > 0)) {
    return [
      ...operators.map((operator) => ({
        start: operator.start,
        text: operator.value === '?:'
          ? '??'
          : operator.value,
      })),
      ...branches.flatMap((branch) => (isValue(translation, branch)
        ? translation.translate(translation, branch.units)
        : getPlaceholderReplacements(branch.units))),
    ];
  }

  const values = branches.filter((branch) => isValue(translation, branch));

  return values.length === 1
    ? values.flatMap((branch) => translation.translate(translation, branch.units))
    : getPlaceholderReplacements(units);
};

const translateList = (translation: Translation, group: Group): Replacement[] => [
  ...getTokenReplacements([group.open, group.close]),
  ...getCommaReplacements(group),
  ...splitUnits(group.units, isComma).flatMap((element) => {
    const values = getBranches(element).filter((branch) => isValue(translation, branch));

    return values.flatMap((branch, index) => [
      ...translation.translate(translation, branch.units),
      ...((index < values.length - 1 && branch.operator !== undefined)
        ? [
          {
            start: branch.operator.start,
            text: ',',
          },
        ]
        : []),
    ]);
  }),
];

const translateEntry = (translation: Translation, element: Unit[]): Replacement[] => {
  const [key, separator, ...value] = element;

  if (key === undefined) {
    return [];
  }

  if (!isToken(separator) || !KEY_SEPARATORS.has(separator.value)) {
    return (separator === undefined && isToken(key))
      ? translation.translate(translation, element)
      : getPlaceholderReplacements(element);
  }

  return [
    ...(isToken(key)
      ? getTokenReplacements([key])
      : [
        {
          start: key.open.start,
          text: '[',
        },
        {
          start: findWritablePosition(translation.text, key.open.end),
          text: PLACEHOLDER,
        },
        {
          start: key.close.start,
          text: ']',
        },
      ]),
    {
      start: separator.start,
      text: ':',
    },
    ...translateExpression(translation, value),
  ];
};

const translateEntries = (translation: Translation, group: Group): Replacement[] => [
  ...getCommaReplacements(group),
  ...splitUnits(group.units, isComma).flatMap((element) => translateEntry(translation, element)),
];

const translateCall = (translation: Translation, call: Call): Maybe<Replacement[]> => {
  const virtualName = getVirtualName(call.name.value);
  const elements = splitUnits(call.arguments.units, isComma);

  if (elements.every((element) => !isNamedArgument(element))) {
    return [
      {
        start: call.name.start,
        text: virtualName,
      },
      ...translateList(translation, call.arguments),
    ];
  }

  if (!/[\t ]/v.test(translation.text.charAt(call.name.start - 1))
    || elements.some((element) => element.length > 0 && !isNamedArgument(element))) {
    return undefined;
  }

  return [
    {
      start: call.name.start - 1,
      text: `${virtualName}(`,
    },
    {
      start: call.arguments.open.start,
      text: '{',
    },
    ...translateEntries(translation, call.arguments),
    {
      start: call.arguments.close.start,
      text: '}',
    },
    {
      start: findWritablePosition(translation.text, call.arguments.close.end),
      text: ')',
    },
  ];
};

const translateItem = (translation: Translation, units: Unit[]): Replacement[] => {
  const call = findCall(translation, units);
  const [unit] = units;

  if (call !== undefined) {
    return translateCall(translation, call) ?? getPlaceholderReplacements(units);
  }

  if (isGroup(unit, '[')) {
    return translateList(translation, unit);
  }

  if (isGroup(unit, '{')) {
    return [
      ...getTokenReplacements([unit.open, unit.close]),
      ...translateEntries(translation, unit),
    ];
  }

  if (isGroup(unit, '(')) {
    return [
      ...getTokenReplacements([unit.open, unit.close]),
      ...translateExpression(translation, unit.units),
    ];
  }

  if (!isToken(unit)) {
    return [];
  }

  return [
    {
      start: unit.start,
      text: /[\n\r]/v.test(unit.value)
        ? `\`${unit.value.slice(1, -1)}\``
        : unit.value,
    },
  ];
};

const findStatements = (translation: Translation): Replacement[][] => {
  const callPattern = createPattern('gv')`(?<![\w.])(?:${translation.functionNames})\s*\(`;
  const statements: Replacement[][] = [];
  let previousEnd = 0;

  for (const match of translation.text.matchAll(callPattern)) {
    const functionName = match[0].slice(0, -1).trimEnd();
    const argumentGroup = findGroup(translation.text, (match.index + match[0].length) - 1);

    const replacements = (argumentGroup === undefined || match.index < previousEnd)
      ? undefined
      : translateCall(translation, {
        name: {
          start: match.index,
          end: match.index + functionName.length,
          value: functionName,
        },
        arguments: argumentGroup,
      });

    if (replacements === undefined || Math.min(...replacements.map((replacement) => replacement.start)) < previousEnd) {
      continue;
    }

    const statement = [
      ...replacements,
      {
        start: findWritablePosition(translation.text, getEnd(replacements)),
        text: ';',
      },
    ];

    statements.push(statement);
    previousEnd = getEnd(statement);
  }

  return statements;
};

const createClassBlock = (text: string, functionNames: string[]): Maybe<string> => {
  const replacements = findStatements({
    text,
    functionNames,
    translate: translateItem,
  })
    .flat()
    .toSorted((first, second) => first.start - second.start);

  if (replacements.length === 0) {
    return undefined;
  }

  let block = '';
  let position = 0;

  for (const replacement of replacements) {
    block += text.slice(position, replacement.start).replaceAll(/[^\n\r]/gv, ' ') + replacement.text;
    position = replacement.start + replacement.text.length;
  }

  return block + text.slice(position).replaceAll(/[^\n\r]/gv, ' ');
};

const getIndentation = (text: string, fix: Rule.Fix): string => text.slice(text.lastIndexOf('\n', fix.range[0] - 1) + 1, fix.range[0]);

const isAloneOnLine = (text: string, fix: Rule.Fix): boolean => /^[\t ]*$/v.test(getIndentation(text, fix))
  && /^,?[\t ]*(?:\r?\n|$)/v.test(text.slice(fix.range[1]));

const mapFixQuotes = (text: string, fix: Rule.Fix): Rule.Fix => {
  const quote = text.charAt(fix.range[0]);

  return (/^["']$/v.test(quote) && fix.text.startsWith('`') && fix.text.endsWith('`'))
    ? {
      range: fix.range,
      text: `${quote}${fix.text.slice(1, -1)}${quote}`,
    }
    : fix;
};

const mapWrappingFix = (text: string, fix: Rule.Fix): Rule.Fix => {
  const quote = text.charAt(fix.range[0]);

  return {
    range: fix.range,
    text: fix.text
      .slice(1, -1)
      .split('\n')
      .map((line) => line.trim())
      .filter((line) => line !== '')
      .map((line) => `${quote}${line}${quote}`)
      .join(`,\n${getIndentation(text, fix)}`),
  };
};

const mapMessage = (text: string, message: Linter.LintMessage): Maybe<Linter.LintMessage> => {
  if (message.ruleId?.startsWith('tailwind/') !== true) {
    return undefined;
  }

  if (message.ruleId !== WRAPPING_RULE) {
    return message.fix === undefined
      ? message
      : {
        ...message,
        fix: mapFixQuotes(text, message.fix),
      };
  }

  if (message.fix?.text.startsWith('`\n') !== true) {
    return undefined;
  }

  return isAloneOnLine(text, message.fix)
    ? {
      ...message,
      message: 'Class string exceeds the maximum line length, split it into array items.',
      fix: mapWrappingFix(text, message.fix),
    }
    : {
      ...message,
      message: 'Class string exceeds the maximum line length.',
      fix: undefined,
    };
};

export const createTwigProcessor = (functions: readonly TwigFunction[]): Linter.Processor => {
  const textsByFilePath = new Map<string, string>();
  const functionNames = functions.map((twigFunction) => twigFunction.name);

  return {
    meta: {
      name: `tailwind/${TWIG_PROCESSOR}`,
    },
    supportsAutofix: true,
    preprocess: (text, filePath): ReturnType<NonNullable<Linter.Processor['preprocess']>> => {
      const block = createClassBlock(text, functionNames);

      textsByFilePath.set(filePath, text);

      return block === undefined
        ? [text]
        : [
          text,
          {
            text: block,
            filename: `0.${BLOCK_EXTENSION}`,
          },
        ];
    },
    postprocess: ([templateMessages = [], blockMessages = []], filePath): Linter.LintMessage[] => {
      const text = textsByFilePath.get(filePath);

      textsByFilePath.delete(filePath);

      return text === undefined
        ? templateMessages
        : [
          ...templateMessages,
          ...blockMessages.flatMap((message) => mapMessage(text, message) ?? []),
        ];
    },
  };
};

/**
 * @throws {Error}
 */
export const getTwigFunctions = (projectFunctions: readonly TwigFunction[] = []): TwigFunction[] => {
  for (const twigFunction of projectFunctions) {
    if (!/^[A-Z_a-z][\w.]*$/v.test(twigFunction.name)) {
      throw new Error(`Invalid Twig function name "${twigFunction.name}".`);
    }

    for (const matcher of twigFunction.match) {
      if (matcher.path !== undefined) {
        compileConfiguredPattern(matcher.path, 'u');
      }
    }
  }

  return new Map([
    ...DEFAULT_FUNCTIONS,
    ...projectFunctions,
  ].map((twigFunction) => [twigFunction.name, twigFunction]))
    .values()
    .toArray();
};

export const twig = (functions: readonly TwigFunction[]): Config[] => (isModuleEnabled(MODULES.html)
  ? [
    {
      name: buildConfigName(MAIN_SCOPES.TAILWIND, SUB_SCOPES.PROCESSOR),
      files: [GLOB_HTML_TWIG],
      processor: `tailwind/${TWIG_PROCESSOR}`,
    },
    {
      name: buildConfigName(MAIN_SCOPES.TAILWIND, `${SUB_SCOPES.RULES}-twig`),
      files: [GLOB_TWIG_CLASS_BLOCKS],
      settings: {
        'better-tailwindcss': {
          selectors: functions.map((twigFunction) => ({
            kind: 'callee',
            name: `^${getVirtualName(twigFunction.name)}$`,
            match: twigFunction.match,
          })),
        },
      },
      rules: {
        [WRAPPING_RULE]: [
          'error',
          {
            indent: INDENT,
            printWidth: MAX_LEN - 1,
          },
        ],
      },
    },
  ]
  : []);
