/**
 * @internal @brnshkr/config/eslint
 */

import path from 'node:path';

import {
  doesFileExist,
  findNearestPackageJson,
  getMtime,
  readJsonFile,
  readTextFile,
  toPosix,
} from '../../../shared/utils/filesystem';

import { objectEntries, objectFromEntries } from '../../../shared/utils/object';

import {
  hasTag,
  TAG_API,
  TAG_INTERNAL,
} from '../../utils/jsdoc';

import { loadTsConfigPaths } from '../../utils/tsconfig';

import type { ParserServicesWithTypeInformation, TSESLint, TSESTree } from '@typescript-eslint/utils';
import type ts from 'typescript';
import type { Maybe } from '../../../shared/types/core';
import type { TsConfigPaths } from '../../utils/tsconfig';
import type { RuleDefinition } from '.';

export const MESSAGE_ID_UNEXPECTED_INTERNAL_USAGE = 'unexpectedInternalUsage';
export const MESSAGE_ID_UNEXPECTED_TARGETED_INTERNAL_USAGE = 'unexpectedTargetedInternalUsage';

const PLAIN_INTERNAL = '@internal';
const PUBLIC_API = '@api';
const NAMESPACE_SEPARATORS = <const>['/', '#', '.'];

const LAYOUT_SEGMENTS = new Set<string>([
  'dist',
  'js',
  'lib',
  'src',
]);

const WILDCARD_SUFFIX = '/*';
const MODULE_SPECIFIER_SEARCH_DEPTH = 4;

const OPTION_NAMES = <const>[
  'allowedInternalTargets',
  'allowedDeclaringNamespaces',
  'allowedCallingNamespaces',
  'allowedSymbols',
];

const MODULE_EXTENSIONS = <const>[
  '.ts',
  '.tsx',
  '.mts',
  '.cts',
  '.d.ts',
  '.js',
  '.jsx',
  '.mjs',
  '.cjs',
];

const IMPORT_SPECIFIER_TYPES = new Set<string>([
  'ImportDefaultSpecifier',
  'ImportNamespaceSpecifier',
  'ImportSpecifier',
]);

type OptionName = typeof OPTION_NAMES[number];
type AllowEntry = string | RegExp;

interface InternalUsageOptions extends Partial<Record<OptionName, AllowEntry[]>> {
  tsConfigPath?: string;
}

interface CommentNode {
  end: number;
  getText: () => string;
}

interface DeclarationNode extends ts.Node {
  jsDoc?: CommentNode[];
  parameters?: readonly ts.Node[];
  moduleSpecifier?: ts.Node;
  name?: {
    getText?: () => string;
  };
  statements?: readonly ts.Node[];
}

interface SourceFileNode extends DeclarationNode {
  fileName: string;
  text: string;
}

interface PackageIdentity {
  packageName: string;
  packageRoot: string;
}

interface PackageCacheEntry {
  mtime: Maybe<number>;
  identity: Maybe<PackageIdentity>;
}

interface ModuleIdentity {
  moduleId: string;
  namespace: string;
  namespaces: string[];
}

interface ModuleVisibility {
  fileVisibility: Maybe<string>;
  visibilityByExportName: Map<string, string>;
  callableExportNames: Set<string>;
}

interface ModuleVisibilityCacheEntry {
  mtime: Maybe<number>;
  visibility: ModuleVisibility;
}

interface InternalSymbol {
  namespace: string;
  namespaces: string[];
  symbolId: string;
  target: string;
}

interface AllowList {
  prefixes: string[];
  patterns: RegExp[];
}

const packageCache = new Map<string, PackageCacheEntry>();
const packageJsonPathCache = new Map<string, Maybe<string>>();
const moduleVisibilityCache = new Map<string, ModuleVisibilityCacheEntry>();
const internalMarkerCache = new WeakMap<SourceFileNode, boolean>();
const fileVisibilityCache = new WeakMap<SourceFileNode, Maybe<string>>();

const trimSeparators = (value: string): string => {
  let start = 0;
  let end = value.length;

  while (start < end && value.startsWith('/', start)) {
    start += 1;
  }

  while (end > start && value.endsWith('/', end)) {
    end -= 1;
  }

  return value.slice(start, end);
};

const isInSubtree = (value: string, prefix: string): boolean => value === prefix
  || NAMESPACE_SEPARATORS.some((separator) => value.startsWith(`${prefix}${separator}`));

const resolveInternalTarget = (comment: string): Maybe<string> => {
  const target = /\*\s+@internal(?=\s|$)(?<target>[^\n]*)/v.exec(comment)?.groups?.['target'];

  if (target === undefined) {
    return undefined;
  }

  const trimmedTarget = target.replace(/\*\/\s*$/v, '').trim();

  return /^[\w\-.\/@]+$/v.test(trimmedTarget) ? trimSeparators(trimmedTarget) : PLAIN_INTERNAL;
};

const findPackageJsonPath = (directory: string): Maybe<string> => {
  if (packageJsonPathCache.has(directory)) {
    return packageJsonPathCache.get(directory);
  }

  const packageJsonPath = findNearestPackageJson(directory);

  packageJsonPathCache.set(directory, packageJsonPath);

  return packageJsonPath;
};

const loadPackageIdentity = (directory: string): Maybe<PackageIdentity> => {
  const packageJsonPath = findPackageJsonPath(directory);

  if (packageJsonPath === undefined) {
    return undefined;
  }

  const mtime = getMtime(packageJsonPath);
  const cacheEntry = packageCache.get(packageJsonPath);

  if (cacheEntry !== undefined && cacheEntry.mtime === mtime) {
    return cacheEntry.identity;
  }

  const manifest = <Maybe<{ name?: unknown }>>readJsonFile(packageJsonPath);
  const packageName = typeof manifest?.name === 'string' ? manifest.name : '';

  const identity = packageName.length === 0
    ? undefined
    : {
      packageName,
      packageRoot: toPosix(path.dirname(packageJsonPath)),
    };

  packageCache.set(packageJsonPath, {
    mtime,
    identity,
  });

  return identity;
};

const dropModuleExtension = (modulePath: string): string => modulePath.replace(/(?:\.d)?\.[cm]?[jt]sx?$/v, '');

const stripLayoutSegments = (modulePath: string): string => {
  const segments = modulePath.split('/');
  let start = 0;

  while (start < segments.length - 1 && LAYOUT_SEGMENTS.has(segments[start] ?? '')) {
    start += 1;
  }

  return segments.slice(start).join('/');
};

const toNamespace = (moduleId: string): string => {
  const lastSeparator = moduleId.lastIndexOf('/');

  return lastSeparator === -1 ? moduleId : moduleId.slice(0, lastSeparator);
};

const toAliasModuleId = (pattern: string, target: string, absolutePath: string): Maybe<string> => {
  if (!pattern.endsWith(WILDCARD_SUFFIX) || !target.endsWith(WILDCARD_SUFFIX)) {
    return dropModuleExtension(target) === dropModuleExtension(absolutePath) ? pattern : undefined;
  }

  const targetRoot = target.slice(0, -WILDCARD_SUFFIX.length);

  if (!absolutePath.startsWith(`${targetRoot}/`)) {
    return undefined;
  }

  const relativePath = stripLayoutSegments(dropModuleExtension(absolutePath.slice(targetRoot.length + 1)));

  return `${pattern.slice(0, -WILDCARD_SUFFIX.length)}/${relativePath}`;
};

const buildAliasNamespaces = (absolutePath: string, aliasPaths: Maybe<TsConfigPaths>): string[] => {
  const namespaces: string[] = [];

  for (const [pattern, targets] of objectEntries(aliasPaths ?? {})) {
    for (const target of targets) {
      const aliasModuleId = toAliasModuleId(pattern, target, absolutePath);

      if (aliasModuleId !== undefined) {
        namespaces.push(toNamespace(aliasModuleId));
      }
    }
  }

  return namespaces;
};

const buildModuleIdentity = (filePath: string, aliasPaths: Maybe<TsConfigPaths>): Maybe<ModuleIdentity> => {
  const absolutePath = toPosix(path.resolve(filePath));
  const identity = loadPackageIdentity(path.dirname(absolutePath));

  if (identity === undefined || !absolutePath.startsWith(`${identity.packageRoot}/`)) {
    return undefined;
  }

  const relativePath = stripLayoutSegments(dropModuleExtension(absolutePath.slice(identity.packageRoot.length + 1)));
  const moduleId = `${identity.packageName}/${relativePath}`;
  const namespace = toNamespace(moduleId);

  return {
    moduleId,
    namespace,
    namespaces: [namespace, ...buildAliasNamespaces(absolutePath, aliasPaths)],
  };
};

const createExportPattern = (): RegExp => new RegExp(
  String.raw`^\s*export\s+(?<defaultKeyword>default\s+)?`
  + String.raw`(?:(?:abstract|async|declare)\s+)*`
  + String.raw`(?<keyword>class|const|enum|function|interface|let|type|var)\s+`
  + String.raw`(?<name>[\p{ID_Start}$_][\p{ID_Continue}$]*)`,
  'v',
);

const readExportName = (following: string): Maybe<string> => {
  const match = createExportPattern().exec(following);

  if (match === null) {
    return undefined;
  }

  return match.groups?.['defaultKeyword'] === undefined ? match.groups?.['name'] : 'default';
};

const hasInternalMarker = (sourceFile: SourceFileNode): boolean => {
  if (internalMarkerCache.has(sourceFile)) {
    return internalMarkerCache.get(sourceFile) === true;
  }

  const hasMarker = sourceFile.text.includes(`@${TAG_INTERNAL}`);

  internalMarkerCache.set(sourceFile, hasMarker);

  return hasMarker;
};

const resolveVisibility = (comment: string): Maybe<string> => resolveInternalTarget(comment)
  ?? (hasTag(comment, TAG_API) ? PUBLIC_API : undefined);

const isCallableExport = (following: string): boolean => createExportPattern()
  .exec(following)
  ?.groups?.['keyword'] === 'function';

const startsWithBlankLine = (following: string): boolean => {
  const [, blankLine, nextLine] = following.split('\n');

  return nextLine !== undefined && (blankLine ?? '').trim().length === 0;
};

const isFileLevelComment = (following: string): boolean => {
  const trimmed = following.trimStart();
  const [firstLine = ''] = trimmed.split('\n');

  return startsWithBlankLine(following)
    || trimmed.startsWith('/**')
    || firstLine.startsWith('import ')
    || (firstLine.startsWith('export ') && firstLine.includes(' from '));
};

const resolveLexicalFileVisibility = (content: string, matches: RegExpExecArray[]): Maybe<string> => {
  const [fileComment] = matches;

  if (fileComment === undefined || !isFileLevelComment(content.slice(fileComment.index + fileComment[0].length))) {
    return undefined;
  }

  return resolveVisibility(fileComment[0]);
};

const buildModuleVisibility = (filePath: string): ModuleVisibility => {
  const content = readTextFile(filePath) ?? '';
  const matches = [...content.matchAll(/\/\*\*(?:[^*]|\*(?!\/))*\*\//gv)];
  const visibilityByExportName = new Map<string, string>();
  const callableExportNames = new Set<string>();

  for (const match of matches) {
    const visibility = resolveVisibility(match[0]);
    const following = content.slice(match.index + match[0].length);
    const exportName = readExportName(following);

    if (exportName === undefined) {
      continue;
    }

    if (visibility !== undefined) {
      visibilityByExportName.set(exportName, visibility);
    }

    if (isCallableExport(following)) {
      callableExportNames.add(exportName);
    }
  }

  return {
    fileVisibility: resolveLexicalFileVisibility(content, matches),
    visibilityByExportName,
    callableExportNames,
  };
};

const loadModuleVisibility = (filePath: string): ModuleVisibility => {
  const mtime = getMtime(filePath);
  const cacheEntry = moduleVisibilityCache.get(filePath);

  if (cacheEntry !== undefined && cacheEntry.mtime === mtime) {
    return cacheEntry.visibility;
  }

  const visibility = buildModuleVisibility(filePath);

  moduleVisibilityCache.set(filePath, {
    mtime,
    visibility,
  });

  return visibility;
};

const resolveRelativeModulePath = (fromFilePath: string, specifier: string): Maybe<string> => {
  if (!specifier.startsWith('.')) {
    return undefined;
  }

  const base = path.resolve(path.dirname(fromFilePath), specifier);

  return [
    ...MODULE_EXTENSIONS.map((extension) => `${base}${extension}`),
    ...MODULE_EXTENSIONS.map((extension) => path.join(base, `index${extension}`)),
  ].find((candidate) => doesFileExist(candidate));
};

const buildAllowList = (optionName: OptionName, entries: Maybe<AllowEntry[]>): AllowList => {
  const prefixes: string[] = [];
  const patterns: RegExp[] = [];

  for (const entry of entries ?? []) {
    if (entry instanceof RegExp) {
      patterns.push(new RegExp(entry.source, entry.flags.replaceAll(/[gy]/gv, '')));

      continue;
    }

    const prefix = typeof entry === 'string' ? trimSeparators(entry) : '';

    if (prefix.length === 0 || !/^[\w@]/v.test(prefix)) {
      throw new Error(
        `Entry "${entry}" for option "${optionName}" is neither a namespace prefix nor a regular expression.`,
      );
    }

    prefixes.push(prefix);
  }

  return {
    prefixes,
    patterns,
  };
};

const isAllowedBy = (value: string, allowList: AllowList): boolean => allowList.prefixes
  .some((prefix) => isInSubtree(value, prefix))
  || allowList.patterns.some((pattern) => pattern.test(value));

const hasModuleSpecifierAbove = (node: DeclarationNode): boolean => {
  let current = <Maybe<DeclarationNode>>node;

  for (let depth = 0; current !== undefined && depth < MODULE_SPECIFIER_SEARCH_DEPTH; depth += 1) {
    if (current.moduleSpecifier !== undefined) {
      return true;
    }

    current = <Maybe<DeclarationNode>>current.parent;
  }

  return false;
};

const findDeclaredVisibility = (declaration: DeclarationNode): Maybe<string> => {
  let current = <Maybe<DeclarationNode>>declaration;

  while (current !== undefined) {
    for (const commentNode of (current.jsDoc ?? []).toReversed()) {
      const visibility = resolveVisibility(commentNode.getText());

      if (visibility !== undefined) {
        return visibility;
      }
    }

    current = <Maybe<DeclarationNode>>current.parent;
  }

  return undefined;
};

const isSeparatedByBlankLine = (
  sourceFile: SourceFileNode,
  commentNode: CommentNode,
  statement: DeclarationNode,
): boolean => startsWithBlankLine(sourceFile.text.slice(commentNode.end, statement.getStart()));

const findFileLevelComment = (sourceFile: SourceFileNode): Maybe<string> => {
  const statement = <Maybe<DeclarationNode>>sourceFile.statements?.[0];
  const commentNodes = statement?.jsDoc ?? [];
  const [fileCommentNode] = commentNodes;

  if (statement === undefined || fileCommentNode === undefined) {
    return undefined;
  }

  if (commentNodes.length > 1 || statement.moduleSpecifier !== undefined) {
    return fileCommentNode.getText();
  }

  return isSeparatedByBlankLine(sourceFile, fileCommentNode, statement)
    ? fileCommentNode.getText()
    : undefined;
};

const findFileVisibility = (sourceFile: SourceFileNode): Maybe<string> => {
  if (fileVisibilityCache.has(sourceFile)) {
    return fileVisibilityCache.get(sourceFile);
  }

  const fileComment = findFileLevelComment(sourceFile);
  const visibility = fileComment === undefined ? undefined : resolveVisibility(fileComment);

  fileVisibilityCache.set(sourceFile, visibility);

  return visibility;
};

const toInternalTarget = (visibility: Maybe<string>): Maybe<string> => (visibility === PUBLIC_API
  ? undefined
  : visibility);

const buildSymbolId = (moduleId: string, symbolName: string, declaration: DeclarationNode): string => {
  const names = [symbolName];
  let current = <Maybe<DeclarationNode>>declaration.parent;

  while (current !== undefined) {
    const name = current.name?.getText?.();

    if (name !== undefined && name.length > 0) {
      names.unshift(name);
    }

    current = <Maybe<DeclarationNode>>current.parent;
  }

  return `${moduleId}#${names.join('.')}${declaration.parameters === undefined ? '' : '()'}`;
};

const isPublicExportName = (node: TSESTree.Identifier): boolean => 'exported' in node.parent
  && node.parent.exported === node;

const getImportedName = (specifier: TSESTree.ImportClause): Maybe<string> => {
  if ('imported' in specifier) {
    return 'name' in specifier.imported ? specifier.imported.name : undefined;
  }

  // eslint-disable-next-line ts/no-unsafe-enum-comparison -- Avoid an explicit dependency on typescript-eslint's enum
  return specifier.type === 'ImportDefaultSpecifier' ? 'default' : undefined;
};

/**
 * @see https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/internal-usage.md
 */
export const internalUsageRule = <const>{
  meta: {
    type: 'problem',
    docs: {
      description: 'Forbid usage of an `@internal` symbol from outside the namespace it is internal to.',
      url: 'https://github.com/brnshkr/config/blob/master/docs/js/eslint/rules/internal-usage.md',
    },
    schema: [
      {
        type: 'object',
        additionalProperties: false,
        properties: {
          ...objectFromEntries(OPTION_NAMES.map((optionName) => [
            optionName,
            {
              type: 'array',
              tsType: '(string | RegExp)[]',
            },
          ])),
          tsConfigPath: {
            type: 'string',
          },
        },
      },
    ],
    messages: {
      [MESSAGE_ID_UNEXPECTED_INTERNAL_USAGE]: '`{{ symbol }}` is internal and must not be used from `{{ caller }}`.',
      [MESSAGE_ID_UNEXPECTED_TARGETED_INTERNAL_USAGE]: '`{{ symbol }}` is internal to `{{ target }}` and must not be used from `{{ caller }}`.',
    },
  },
  create: (context) => {
    const options = <InternalUsageOptions>(context.options[0] ?? {});

    const allowLists = {
      allowedCallingNamespaces: buildAllowList('allowedCallingNamespaces', options.allowedCallingNamespaces),
      allowedDeclaringNamespaces: buildAllowList('allowedDeclaringNamespaces', options.allowedDeclaringNamespaces),
      allowedInternalTargets: buildAllowList('allowedInternalTargets', options.allowedInternalTargets),
      allowedSymbols: buildAllowList('allowedSymbols', options.allowedSymbols),
    };

    const sourceCode = <TSESLint.SourceCode><unknown>context.sourceCode;
    const services = <Maybe<ParserServicesWithTypeInformation>><unknown>sourceCode.parserServices;
    const program = <Maybe<ts.Program>>services?.program;
    const typeChecker = program?.getTypeChecker();
    const aliasPaths = options.tsConfigPath === undefined ? undefined : loadTsConfigPaths(options.tsConfigPath);
    const resolveIdentity = (filePath: string): Maybe<ModuleIdentity> => buildModuleIdentity(filePath, aliasPaths);
    const callerIdentity = resolveIdentity(context.filename);

    if (callerIdentity === undefined) {
      return {};
    }

    const callerNamespace = callerIdentity.namespace;
    const callerNamespaces = callerIdentity.namespaces;

    const isReachable = (internal: InternalSymbol): boolean => {
      const roots = internal.target === PLAIN_INTERNAL ? internal.namespaces : [internal.target];

      return callerNamespaces.some((namespace) => roots.some((root) => isInSubtree(namespace, root)));
    };

    const isUsageAllowed = (
      internal: InternalSymbol,
    ): boolean => isAllowedBy(internal.target, allowLists.allowedInternalTargets)
      || internal.namespaces.some((namespace) => isAllowedBy(namespace, allowLists.allowedDeclaringNamespaces))
      || callerNamespaces.some((namespace) => isAllowedBy(namespace, allowLists.allowedCallingNamespaces))
      || isAllowedBy(internal.symbolId, allowLists.allowedSymbols)
      || isReachable(internal);

    const reportUsage = (node: TSESTree.Node, internal: InternalSymbol): void => {
      if (isUsageAllowed(internal)) {
        return;
      }

      context.report({
        node,
        messageId: internal.target === PLAIN_INTERNAL
          ? MESSAGE_ID_UNEXPECTED_INTERNAL_USAGE
          : MESSAGE_ID_UNEXPECTED_TARGETED_INTERNAL_USAGE,
        data: {
          caller: callerNamespace,
          symbol: internal.symbolId,
          target: internal.target,
        },
      });
    };

    const buildInternalSymbol = (
      identity: ModuleIdentity,
      symbolId: string,
      visibility: Maybe<string>,
    ): Maybe<InternalSymbol> => {
      const target = toInternalTarget(visibility);

      return target === undefined
        ? undefined
        : {
          namespace: identity.namespace,
          namespaces: identity.namespaces,
          symbolId,
          target,
        };
    };

    const checkResolvedSymbol = (
      node: TSESTree.Node,
      symbol: ts.Symbol,
      currentSourceFile: ts.SourceFile,
    ): void => {
      const declaration = <Maybe<DeclarationNode>>symbol.declarations?.[0];

      if (declaration === undefined) {
        return;
      }

      const sourceFile = <SourceFileNode>declaration.getSourceFile();

      if (sourceFile === currentSourceFile || !hasInternalMarker(sourceFile)) {
        return;
      }

      const target = toInternalTarget(findDeclaredVisibility(declaration) ?? findFileVisibility(sourceFile));
      const identity = target === undefined ? undefined : resolveIdentity(sourceFile.fileName);

      if (identity === undefined || target === undefined) {
        return;
      }

      reportUsage(node, {
        namespace: identity.namespace,
        namespaces: identity.namespaces,
        symbolId: buildSymbolId(identity.moduleId, symbol.getName(), declaration),
        target,
      });
    };

    const checkTypedIdentifier = (node: TSESTree.Identifier, checker: ts.TypeChecker): void => {
      const tsNode = services?.esTreeNodeToTSNodeMap.get(node);
      const symbol = tsNode === undefined ? undefined : checker.getSymbolAtLocation(tsNode);
      const declaration = <Maybe<DeclarationNode>>symbol?.declarations?.[0];

      if (tsNode === undefined || symbol === undefined || declaration === undefined) {
        return;
      }

      checkResolvedSymbol(
        node,
        hasModuleSpecifierAbove(declaration) ? checker.getAliasedSymbol(symbol) : symbol,
        tsNode.getSourceFile(),
      );
    };

    const checkTypedModule = (node: TSESTree.Node, checker: ts.TypeChecker): void => {
      const tsNode = services?.esTreeNodeToTSNodeMap.get(node);
      const symbol = tsNode === undefined ? undefined : checker.getSymbolAtLocation(tsNode);
      const sourceFile = <Maybe<SourceFileNode>>symbol?.declarations?.[0];

      if (sourceFile === undefined || !hasInternalMarker(sourceFile)) {
        return;
      }

      const identity = resolveIdentity(sourceFile.fileName);

      const internal = identity === undefined
        ? undefined
        : buildInternalSymbol(identity, identity.moduleId, findFileVisibility(sourceFile));

      if (internal !== undefined) {
        reportUsage(node, internal);
      }
    };

    const resolveScannedSymbol = (specifier: string, importedName?: string): Maybe<InternalSymbol> => {
      const modulePath = resolveRelativeModulePath(context.filename, specifier);
      const identity = modulePath === undefined ? undefined : resolveIdentity(modulePath);

      if (modulePath === undefined || identity === undefined) {
        return undefined;
      }

      const moduleVisibility = loadModuleVisibility(modulePath);

      return buildInternalSymbol(
        identity,
        importedName === undefined
          ? identity.moduleId
          : `${identity.moduleId}#${importedName}${moduleVisibility.callableExportNames.has(importedName) ? '()' : ''}`,
        (importedName === undefined
          ? undefined
          : moduleVisibility.visibilityByExportName.get(importedName)) ?? moduleVisibility.fileVisibility,
      );
    };

    const checkScannedImport = (node: TSESTree.ImportDeclaration): void => {
      for (const specifier of node.specifiers) {
        const internal = resolveScannedSymbol(node.source.value, getImportedName(specifier));

        if (internal === undefined) {
          continue;
        }

        for (const variable of sourceCode.getDeclaredVariables(node)) {
          if (variable.name !== specifier.local.name) {
            continue;
          }

          for (const reference of variable.references) {
            reportUsage(reference.identifier, internal);
          }
        }
      }
    };

    const checkScannedReexport = (node: TSESTree.ExportNamedDeclaration): void => {
      for (const specifier of node.specifiers) {
        const localName = 'name' in specifier.local ? specifier.local.name : undefined;
        const internal = node.source === null ? undefined : resolveScannedSymbol(node.source.value, localName);

        if (internal !== undefined) {
          reportUsage(specifier, internal);
        }
      }
    };

    if (services === undefined || typeChecker === undefined) {
      return {
        ExportAllDeclaration: (node: TSESTree.ExportAllDeclaration): void => {
          const internal = resolveScannedSymbol(node.source.value);

          if (internal !== undefined) {
            reportUsage(node, internal);
          }
        },
        ExportNamedDeclaration: (node: TSESTree.ExportNamedDeclaration): void => {
          checkScannedReexport(node);
        },
        ImportDeclaration: (node: TSESTree.ImportDeclaration): void => {
          checkScannedImport(node);
        },
        ImportExpression: (node: TSESTree.ImportExpression): void => {
          const specifier = ('value' in node.source && typeof node.source.value === 'string')
            ? node.source.value
            : undefined;

          const internal = specifier === undefined ? undefined : resolveScannedSymbol(specifier);

          if (internal !== undefined) {
            reportUsage(node, internal);
          }
        },
      };
    }

    return {
      ExportAllDeclaration: (node: TSESTree.ExportAllDeclaration): void => {
        checkTypedModule(node.source, typeChecker);
      },
      Identifier: (node: TSESTree.Identifier): void => {
        if (!IMPORT_SPECIFIER_TYPES.has(node.parent.type) && !isPublicExportName(node)) {
          checkTypedIdentifier(node, typeChecker);
        }
      },
      ImportExpression: (node: TSESTree.ImportExpression): void => {
        checkTypedModule(node.source, typeChecker);
      },
    };
  },
} satisfies RuleDefinition;
