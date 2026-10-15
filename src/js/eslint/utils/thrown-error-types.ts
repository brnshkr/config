/**
 * @internal @brnshkr/config/eslint
 */

import type ts from 'typescript';
import type { Maybe } from '#shared/types/core.ts';

export const ERROR_KIND_THROWS = 'throws';
export const ERROR_KIND_REJECTS = 'rejects';
export const UNKNOWN_TYPE_NAME = 'unknown';

export interface ThrownType {
  name: string;
  lineage: readonly string[];
}

export type ErrorKind = typeof ERROR_KIND_THROWS | typeof ERROR_KIND_REJECTS;

export interface ThrownError extends ThrownType {
  kind: ErrorKind;
}

const UNKNOWN_THROWN_TYPE = <const>{
  name: UNKNOWN_TYPE_NAME,
  lineage: [],
} satisfies ThrownType;

/* eslint-disable ts/no-unsafe-enum-assignment -- Avoid an explicit dependency on typescript's enum */
const TYPE_SYMBOL_FLAGS: ts.SymbolFlags.Type = 788_968;
const ANY_TYPE_FLAGS: ts.TypeFlags.Any = 1;
const UNKNOWN_TYPE_FLAGS: ts.TypeFlags.Unknown = 2;
/* eslint-enable ts/no-unsafe-enum-assignment -- Restore rule */
const resolvedTypesByChecker = new WeakMap<ts.TypeChecker, Map<string, ThrownType>>();
const createDeclaredTagPattern = (): RegExp => /@(?<tag>exception|rejects|throws)\b[\t ]*(?:\{(?<type>[^\}]*)\})?/gv;

export const isUncheckedType = (type: ts.Type): boolean => type.flags === ANY_TYPE_FLAGS
  || type.flags === UNKNOWN_TYPE_FLAGS;

export const getDeclaredTypeNames = (comment: Maybe<string>, kind: ErrorKind): string[] => (comment ?? '')
  .matchAll(createDeclaredTagPattern())
  .filter((declaredTag) => (declaredTag.groups?.['tag'] === ERROR_KIND_REJECTS) === (kind === ERROR_KIND_REJECTS))
  .flatMap((declaredTag) => (declaredTag.groups?.['type'] ?? UNKNOWN_TYPE_NAME).split('|'))
  .map((typeName) => typeName.trim().replace(/^.*\./v, ''))
  .filter(Boolean)
  .toArray();

const getTypeLineage = (checker: ts.TypeChecker, type: ts.Type): string[] => {
  const symbolName = type.getSymbol()?.getName();

  if (symbolName === undefined) {
    return [];
  }

  const baseTypes = type.isClassOrInterface()
    ? checker.getBaseTypes(type)
    : [];

  return [
    symbolName,
    ...baseTypes.flatMap((baseType) => getTypeLineage(checker, baseType)),
  ];
};

export const getThrownTypes = (checker: Maybe<ts.TypeChecker>, type: Maybe<ts.Type>): ThrownType[] => {
  if (checker === undefined || type === undefined) {
    return [UNKNOWN_THROWN_TYPE];
  }

  if (type.isUnion()) {
    return type.types.flatMap((memberType) => getThrownTypes(checker, memberType));
  }

  const lineage = getTypeLineage(checker, type);

  return [
    lineage[0] === undefined
      ? UNKNOWN_THROWN_TYPE
      : {
        name: lineage[0],
        lineage,
      },
  ];
};

export const resolveDeclaredType = (checker: ts.TypeChecker, location: ts.Node, typeName: string): ThrownType => {
  if (typeName === UNKNOWN_TYPE_NAME) {
    return UNKNOWN_THROWN_TYPE;
  }

  const sourceFile = location.getSourceFile();
  const cacheKey = `${sourceFile.fileName}:${typeName}`;
  const resolvedTypes = resolvedTypesByChecker.get(checker) ?? new Map<string, ThrownType>();
  const cachedType = resolvedTypes.get(cacheKey);

  if (cachedType !== undefined) {
    return cachedType;
  }

  const symbol = checker
    .getSymbolsInScope(sourceFile, TYPE_SYMBOL_FLAGS)
    .find((candidate) => candidate.getName() === typeName);

  const resolvedType = {
    name: typeName,
    lineage: symbol === undefined
      ? [typeName]
      : getTypeLineage(checker, checker.getDeclaredTypeOfSymbol(symbol)),
  };

  resolvedTypes.set(cacheKey, resolvedType);
  resolvedTypesByChecker.set(checker, resolvedTypes);

  return resolvedType;
};
