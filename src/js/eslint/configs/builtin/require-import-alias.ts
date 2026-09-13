/**
 * @internal @brnshkr/config/eslint
 */

import path from 'node:path';

import { toPosix } from '../../../shared/utils/filesystem';
import { objectEntries } from '../../../shared/utils/object';
import { loadTsConfigPaths, resolveTsConfigPath } from '../../utils/tsconfig';

import type { TSESTree } from '@typescript-eslint/utils';
import type { Maybe } from '../../../shared/types/core';
import type { TsConfigPaths } from '../../utils/tsconfig';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_EXPECTED_ALIAS = 'expectedAlias';
export const MESSAGE_ID_MISSING_ALIAS = 'missingAlias';

const WILDCARD_SUFFIX = '/*';
const WILDCARD_SUFFIX_LENGTH = WILDCARD_SUFFIX.length;
const RELATIVE_SPECIFIER_PREFIXES = <const>['./', '../'];

interface AliasMapping {
  prefix: string;
  baseDirectory: string;
}

interface RequireImportAliasOptions {
  aliases?: Record<string, string[]>;
  tsConfigPath?: string;
  ignoredPaths?: string[];
}

const resolveAliases = (options: RequireImportAliasOptions): TsConfigPaths => options.aliases
  ?? loadTsConfigPaths(options.tsConfigPath ?? resolveTsConfigPath())
  ?? {};

const buildAliasMappings = (aliases: Record<string, string[]>): AliasMapping[] => objectEntries(aliases)
  .filter(([pattern]) => pattern.endsWith(WILDCARD_SUFFIX))
  .flatMap(([pattern, targets]): AliasMapping[] => {
    const prefix = pattern.slice(0, -WILDCARD_SUFFIX_LENGTH);

    return targets
      .filter((target) => target.endsWith(WILDCARD_SUFFIX))
      .map((target) => ({
        prefix,
        baseDirectory: toPosix(target).slice(0, -WILDCARD_SUFFIX_LENGTH),
      }));
  });

const buildAliasedSpecifier = (
  { prefix, baseDirectory }: AliasMapping,
  absolutePath: string,
): Maybe<string> => {
  if (absolutePath === baseDirectory) {
    return prefix;
  }

  return absolutePath.startsWith(`${baseDirectory}/`)
    ? `${prefix}/${absolutePath.slice(baseDirectory.length + 1)}`
    : undefined;
};

const compareSpecifiers = (left: string, right: string): number => {
  const segmentDifference = left.split('/').length - right.split('/').length;

  if (segmentDifference !== 0) {
    return segmentDifference;
  }

  const lengthDifference = left.length - right.length;

  return lengthDifference === 0 ? left.localeCompare(right) : lengthDifference;
};

const findAliasReplacement = (mappings: AliasMapping[], absolutePath: string): Maybe<string> => mappings
  .map((mapping) => buildAliasedSpecifier(mapping, absolutePath))
  .filter((specifier) => specifier !== undefined)
  .toSorted(compareSpecifiers)[0];

const resolveAliasedPath = (mappings: AliasMapping[], source: string): Maybe<string> => {
  for (const { prefix, baseDirectory } of mappings) {
    if (source === prefix) {
      return baseDirectory;
    }

    if (source.startsWith(`${prefix}/`)) {
      return `${baseDirectory}/${source.slice(prefix.length + 1)}`;
    }
  }

  return undefined;
};

const isRelativeSpecifier = (source: string): boolean => RELATIVE_SPECIFIER_PREFIXES.some(
  (prefix) => source.startsWith(prefix),
);

const isFileIgnored = (filename: string, patterns: string[]): boolean => {
  const absoluteFilename = toPosix(filename);
  const relativeFilename = toPosix(path.relative(process.cwd(), filename));

  return patterns.some(
    (pattern) => path.matchesGlob(absoluteFilename, pattern) || path.matchesGlob(relativeFilename, pattern),
  );
};

const getQuote = (sourceNode: TSESTree.Node): string => (('raw' in sourceNode
  && typeof sourceNode.raw === 'string'
  && sourceNode.raw.startsWith('"'))
  ? '"'
  : '\'');

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/require-import-alias.md
 */
export const requireImportAliasRule = <const>{
  meta: {
    type: 'suggestion',
    fixable: 'code',
    docs: {
      description: 'Require imports to use the TypeScript path alias with the fewest path segments when the target file is reachable through one.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/require-import-alias.md',
    },
    schema: [
      {
        type: 'object',
        additionalProperties: false,
        properties: {
          aliases: {
            type: 'object',
            additionalProperties: {
              type: 'array',
              items: {
                type: 'string',
              },
            },
          },
          tsConfigPath: {
            type: 'string',
          },
          ignoredPaths: {
            type: 'array',
            items: {
              type: 'string',
            },
          },
        },
      },
    ],
    messages: {
      [MESSAGE_ID_EXPECTED_ALIAS]: 'Import path \'{{ source }}\' must use the configured alias \'{{ alias }}\'.',
      [MESSAGE_ID_MISSING_ALIAS]: 'Import path \'{{ source }}\' resolves outside any configured TypeScript path alias. Add an alias for this location, remove all other aliases, or disable this rule.',
    },
  },
  create: (context) => {
    const options = <RequireImportAliasOptions>(context.options[0] ?? {});
    const ignoredPaths = options.ignoredPaths ?? [];
    const mappings = buildAliasMappings(resolveAliases(options));

    if (mappings.length === 0 || isFileIgnored(context.filename, ignoredPaths)) {
      return {};
    }

    const fileDirectory = toPosix(path.dirname(context.filename));

    const checkSource = (sourceNode: Maybe<TSESTree.Node> | null): void => {
      // eslint-disable-next-line ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum
      if (sourceNode?.type !== 'Literal' || typeof sourceNode.value !== 'string') {
        return;
      }

      const source = sourceNode.value;

      const absolutePath = isRelativeSpecifier(source)
        ? path.posix.normalize(`${fileDirectory}/${source}`)
        : resolveAliasedPath(mappings, source);

      if (absolutePath === undefined) {
        return;
      }

      const replacement = findAliasReplacement(mappings, absolutePath);

      if (replacement === source) {
        return;
      }

      if (replacement === undefined) {
        context.report({
          node: sourceNode,
          messageId: MESSAGE_ID_MISSING_ALIAS,
          data: {
            source,
          },
        });

        return;
      }

      context.report({
        node: sourceNode,
        messageId: MESSAGE_ID_EXPECTED_ALIAS,
        data: {
          source,
          alias: replacement,
        },
        fix: (fixer) => fixer.replaceText(sourceNode, `${getQuote(sourceNode)}${replacement}${getQuote(sourceNode)}`),
      });
    };

    return {
      ImportDeclaration: (node: TSESTree.ImportDeclaration): void => {
        checkSource(node.source);
      },
      ExportNamedDeclaration: (node: TSESTree.ExportNamedDeclaration): void => {
        checkSource(node.source);
      },
      ExportAllDeclaration: (node: TSESTree.ExportAllDeclaration): void => {
        checkSource(node.source);
      },
      ImportExpression: (node: TSESTree.ImportExpression): void => {
        checkSource(node.source);
      },
    };
  },
} satisfies RuleDefinition;
