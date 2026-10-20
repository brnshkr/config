/**
 * @internal @brnshkr/config/stylelint
 */

import path from 'node:path';

import postcss from 'postcss';
import stylelint from 'stylelint';

import { getEnvironmentValue } from '#shared/utils/environment.ts';
import { findDocblockFencedCodes, findMarkdownFencedCodes, replaceFencedCode } from '#shared/utils/fenced-code.ts';
import { doesFileExist } from '#shared/utils/filesystem.ts';

import {
  GLOB_JS_TS,
  GLOB_MD,
  GLOB_PHP,
  MARKDOWN_EXTENSIONS,
} from '#shared/utils/globs.ts';

import { resolvePackagesSharedAsynchronously } from '#shared/utils/module.ts';
import { packageOrganization } from '#shared/utils/package-json.ts';
import { getConfigDirectory } from '#shared/utils/project-directory.ts';
import { MODULES } from '#stylelint/utils/module.ts';

import type { Root } from 'postcss';

import type {
  Config,
  Plugin,
  Rule,
  Warning,
} from 'stylelint';

import type { Maybe } from '#shared/types/core.ts';
import type { FencedCode } from '#shared/utils/fenced-code.ts';

interface LintedFencedCode {
  fencedCode: FencedCode;
  fixedCode: string;
  warnings: Warning[];
}

type ConfigSource = { config: Config } | { configFile: string };
type RuleResult = Parameters<ReturnType<Rule>>[1];

const RULE_NAME = `${packageOrganization}/fenced-code`;
const STYLELINT_CONFIG_VARIABLE = 'BRNSHKR_STYLELINT_CONFIG';
const CSS_LANGUAGE = 'css';
const FENCED_CODE_MARKER = 'fenced';

const CONFIG_FILE_NAMES = <const>[
  'stylelint.mjs',
  'stylelint.dist.mjs',
];

export const GLOB_FENCED_CODE_FILES = <const>[
  GLOB_MD,
  GLOB_JS_TS,
  GLOB_PHP,
] satisfies string[];

export const GLOB_FENCED_CODE_BLOCKS = `**/*.${FENCED_CODE_MARKER}.*`;

const getBlockFilePath = (filePath: string, index: number, language: string): string => path.join(
  filePath,
  `${String(index)}.${FENCED_CODE_MARKER}.${language}`,
);

const getRawText = (value: unknown): Maybe<string> => (typeof value === 'string' ? value : undefined);

export const fencedCodeSyntax = <const>{
  parse: (source: string | { toString: () => string }, options?: postcss.ProcessOptions): Root => {
    const root = postcss.root();

    root.raws['originalSource'] = source.toString();

    root.source = {
      input: new postcss.Input('', options),
      start: {
        line: 1,
        column: 1,
        offset: 0,
      },
    };

    return root;
  },
  stringify: (node: postcss.AnyNode, builder: postcss.Builder): void => {
    const { raws } = <Root>node;

    builder(getRawText(raws['fixedSource']) ?? getRawText(raws['originalSource']) ?? '', node);
  },
} satisfies postcss.Syntax;

const getConfigSource = (ownConfig: Config): ConfigSource => {
  const configuredFile = getEnvironmentValue(STYLELINT_CONFIG_VARIABLE) ?? '';

  const configFile = configuredFile === ''
    ? CONFIG_FILE_NAMES
      .map((fileName) => path.resolve(getConfigDirectory(), fileName))
      .find((candidateFile) => doesFileExist(candidateFile))
    : configuredFile;

  return configFile === undefined ? { config: ownConfig } : { configFile };
};

/**
 * @rejects {Error}
 */
const findFencedCodes = async (source: string, filePath: string): Promise<FencedCode[]> => {
  const {
    requiredAll: [markdownPlugin],
  } = await resolvePackagesSharedAsynchronously(MODULES.markdown);

  if (markdownPlugin === undefined) {
    return [];
  }

  const isMarkdown = MARKDOWN_EXTENSIONS.some((extension) => filePath.toLowerCase().endsWith(`.${extension}`));

  return (isMarkdown ? findMarkdownFencedCodes : findDocblockFencedCodes)(markdownPlugin.languages.commonmark, source);
};

/**
 * @rejects {Error}
 */
const isStyleLanguage = async (language: string, filePath: string, configSource: ConfigSource): Promise<boolean> => {
  if (language === CSS_LANGUAGE) {
    return true;
  }

  const resolvedConfig = await stylelint.resolveConfig(getBlockFilePath(filePath, 0, language), configSource);
  const customSyntax = resolvedConfig?.customSyntax;

  return customSyntax !== undefined && customSyntax !== fencedCodeSyntax;
};

/**
 * @rejects {Error}
 */
const lintFencedCodes = async (
  fencedCodes: FencedCode[],
  filePath: string,
  isFixing: boolean,
  configSource: ConfigSource,
): Promise<LintedFencedCode[]> => {
  const languages = [...new Set(fencedCodes.map(({ language }) => language))];

  const styleLanguageResults = await Promise.all(languages.map(
    async (language) => isStyleLanguage(language, filePath, configSource),
  ));

  const styleLanguages = new Set(languages.filter((_language, index) => styleLanguageResults.at(index) === true));
  const styleFencedCodes = fencedCodes.filter(({ language }) => styleLanguages.has(language));

  const lintResults = await Promise.all(styleFencedCodes.map(async (fencedCode, index) => stylelint.lint({
    ...configSource,
    code: `${fencedCode.code}\n`,
    codeFilename: getBlockFilePath(filePath, index, fencedCode.language),
    fix: isFixing,
  })));

  return styleFencedCodes.map((fencedCode, index) => {
    const lintResult = lintResults.at(index);

    return {
      fencedCode,
      fixedCode: lintResult?.code?.replace(/\n$/v, '') ?? fencedCode.code,
      warnings: lintResult?.results.at(0)?.warnings ?? [],
    };
  });
};

const reportWarning = (root: Root, result: RuleResult, fencedCode: FencedCode, warning: Warning): void => {
  const columnOffset = fencedCode.linePrefix.length;

  stylelint.utils.report({
    ruleName: warning.rule,
    result,
    node: root,
    message: warning.text,
    severity: warning.severity,
    start: {
      line: fencedCode.firstLineIndex + warning.line,
      column: warning.column + columnOffset,
    },
    end: {
      line: fencedCode.firstLineIndex + (warning.endLine ?? warning.line),
      column: (warning.endColumn ?? (warning.column + 1)) + columnOffset,
    },
  });
};

const applyLintResults = (
  root: Root,
  result: RuleResult,
  source: string,
  lintedFencedCodes: LintedFencedCode[],
): void => {
  let fixedSource = source;

  for (const { fencedCode, warnings } of lintedFencedCodes) {
    for (const warning of warnings) {
      reportWarning(
        root,
        result,
        fencedCode,
        warning,
      );
    }
  }

  for (const { fencedCode, fixedCode } of lintedFencedCodes.toReversed()) {
    fixedSource = fixedCode === fencedCode.code ? fixedSource : replaceFencedCode(fixedSource, fencedCode, fixedCode);
  }

  if (fixedSource !== source) {
    root.raws['fixedSource'] = fixedSource;
  }
};

/**
 * @rejects {Error}
 */
const checkFencedCodes = async (
  root: Root,
  result: RuleResult,
  isFixing: boolean,
  ownConfig: Config,
): Promise<void> => {
  const source = getRawText(root.raws['originalSource']);
  const filePath = root.source?.input.file;

  if (source === undefined || filePath === undefined) {
    return;
  }

  const fencedCodes = await findFencedCodes(source, filePath);

  if (fencedCodes.length === 0) {
    return;
  }

  applyLintResults(
    root,
    result,
    source,
    await lintFencedCodes(
      fencedCodes,
      filePath,
      isFixing,
      getConfigSource(ownConfig),
    ),
  );
};

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/stylelint/rules/fenced-code.md
 */
export const createFencedCodePlugin = (ownConfig: Config): Plugin => {
  const ruleFunction: Rule<boolean> = (isEnabled, _secondaryOptions, context) => async (root, result) => (
    isEnabled
      ? checkFencedCodes(
        root,
        result,
        context.fix === true,
        ownConfig,
      )
      : undefined
  );

  ruleFunction.ruleName = RULE_NAME;
  ruleFunction.messages = {};

  ruleFunction.meta = {
    url: 'https://github.com/brnshkr/config/blob/master/docs/js/stylelint/rules/fenced-code.md',
  };

  return stylelint.createPlugin(RULE_NAME, ruleFunction);
};
