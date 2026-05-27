import path from 'node:path';

import { toPosix } from '../../../shared/utils/filesystem';
import { objectEntries } from '../../../shared/utils/object';
import { loadTsConfigPaths, resolveTsConfigPath } from '../../utils/tsconfig';

import type { TSESTree } from '@typescript-eslint/utils';
import type { Maybe } from '../../../shared/types/core';
import type { TsConfigPaths } from '../../utils/tsconfig';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_PREFER_ALIAS = 'preferAlias';
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
  })
  .toSorted((left, right) => right.baseDirectory.length - left.baseDirectory.length);

const findAliasReplacement = (mappings: AliasMapping[], absolutePath: string): Maybe<string> => {
  for (const { prefix, baseDirectory } of mappings) {
    if (absolutePath === baseDirectory) {
      return prefix;
    }

    if (absolutePath.startsWith(`${baseDirectory}/`)) {
      return `${prefix}/${absolutePath.slice(baseDirectory.length + 1)}`;
    }
  }

  return undefined;
};

const isRelativeSpecifier = (source: string): boolean => RELATIVE_SPECIFIER_PREFIXES.some(
  (prefix) => source.startsWith(prefix),
);

const isAlreadyAliased = (source: string, mappings: AliasMapping[]): boolean => mappings.some(
  ({ prefix }) => source === prefix || source.startsWith(`${prefix}/`),
);

/* eslint-disable node/no-unsupported-features/node-builtins -- 'path.matchesGlob' is stable enough for our supported runtimes (Bun + Node 22+) */
const isFileIgnored = (filename: string, patterns: string[]): boolean => patterns.some(
  (pattern) => path.matchesGlob(toPosix(filename), pattern)
    || path.matchesGlob(toPosix(path.relative(process.cwd(), filename)), pattern),
);
/* eslint-enable node/no-unsupported-features/node-builtins -- Restore rule */

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
      description: 'Require imports to use TypeScript path aliases when the target file is reachable through a configured alias.',
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
      [MESSAGE_ID_PREFER_ALIAS]: 'Import path \'{{ source }}\' must use the configured alias \'{{ alias }}\'.',
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

      if (!isRelativeSpecifier(source) || isAlreadyAliased(source, mappings)) {
        return;
      }

      const absolutePath = path.posix.normalize(`${fileDirectory}/${source}`);
      const replacement = findAliasReplacement(mappings, absolutePath);

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
        messageId: MESSAGE_ID_PREFER_ALIAS,
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
