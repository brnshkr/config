/**
 * @internal @brnshkr/config/eslint
 */

import type { Maybe } from '../../shared/types/core';

/**
 * Auxiliary, modal, and copula verbs.
 * Boolish in either direction, on any kind.
 */
const AUXILIARY_PREFIXES = <const>[
  'are',
  'can',
  'did',
  'does',
  'has',
  'is',
  'may',
  'should',
  'was',
  'will',
];

/**
 * Capability and need verbs.
 * Read as boolean value flags too, so boolish in either direction, on any kind.
 */
const CAPABILITY_PREFIXES = <const>[
  'expects',
  'needs',
  'prefers',
  'requires',
  'supports',
  'wants',
];

/**
 * Object-relation predicate verbs.
 * Boolish in either direction, on any kind — `allowsNull` reads as a flag, `equals()` as a predicate.
 */
const RELATIONAL_PREFIXES = <const>[
  'accepts',
  'allows',
  'belongs',
  'contains',
  'covers',
  'denies',
  'depends',
  'disallows',
  'equals',
  'excludes',
  'exists',
  'extends',
  'handles',
  'ignores',
  'implements',
  'includes',
  'intersects',
  'owns',
  'provides',
  'rejects',
  'satisfies',
  'uses',
];

/**
 * Verbs whose third-person form is a canonical non-boolean value (`matches`, `startsAt`, `endsAt`)
 * — reserved on methods and functions only, never on value-holders.
 */
const COLLIDER_PREFIXES = <const>[
  'ends',
  'matches',
  'starts',
];

/**
 * Command verbs — allowed on a boolean return, never reserved, since `doReset(): void` and the like
 * legitimately return a non-boolean.
 */
const DIRECTIVE_PREFIXES = <const>[
  'do',
];

/**
 * Prefixes a non-boolean value-holder must not start with.
 * The auxiliary, capability, and object-relation verbs — each reads as a boolean flag on a value.
 */
export const RESERVED_VALUE_PREFIXES = [
  ...AUXILIARY_PREFIXES,
  ...CAPABILITY_PREFIXES,
  ...RELATIONAL_PREFIXES,
];

/**
 * Prefixes a non-boolean method or function must not start with.
 * The value-reserved set plus the colliders — they read as a predicate on a method, data on a value.
 */
export const RESERVED_METHOD_PREFIXES = [
  ...RESERVED_VALUE_PREFIXES,
  ...COLLIDER_PREFIXES,
];

/**
 * Prefixes a boolean-returning method or function may start with.
 * The method-reserved set plus the `do` directive, which commands may also use on non-boolean returns.
 */
export const PREDICATE_PREFIXES = [
  ...RESERVED_METHOD_PREFIXES,
  ...DIRECTIVE_PREFIXES,
];

/**
 * Prefixes a boolean value-holder may start with.
 * The value-reserved set plus the `do` directive and the representation flag `as`.
 */
export const FLAG_PREFIXES = [
  ...RESERVED_VALUE_PREFIXES,
  ...DIRECTIVE_PREFIXES,
  'as',
];

export const KIND_CONSTANT = 'Constant';
export const KIND_FUNCTION = 'Function';
export const KIND_METHOD = 'Method';
export const KIND_PARAMETER = 'Parameter';
export const KIND_PROPERTY = 'Property';
export const KIND_VARIABLE = 'Variable';

const CALLABLE_KINDS = new Set([
  KIND_FUNCTION,
  KIND_METHOD,
]);

const isCallableKind = (kind: string): boolean => CALLABLE_KINDS.has(kind);
const getFirstWord = (name: string): string => (/^(?:[0-9a-z]+|[0-9A-Z]+)/v.exec(name)?.[0] ?? '').toLowerCase();

const getBooleanConverterToken = (name: string, kind: string): Maybe<string> => (isCallableKind(kind)
  ? /^(?:as|to)bool(?:ean)?/iv.exec(name)?.[0]
  : undefined);

export const getPrefixesForKind = (kind: string): string[] => (isCallableKind(kind)
  ? PREDICATE_PREFIXES
  : FLAG_PREFIXES);

export const isBoolishName = (name: string, kind: string): boolean => getPrefixesForKind(kind)
  .includes(getFirstWord(name))
  || getBooleanConverterToken(name, kind) !== undefined;

export const getReservedToken = (name: string, kind: string): Maybe<string> => {
  const converterToken = getBooleanConverterToken(name, kind);

  if (converterToken !== undefined) {
    return converterToken;
  }

  const firstWord = getFirstWord(name);
  const reservedPrefixes = isCallableKind(kind) ? RESERVED_METHOD_PREFIXES : RESERVED_VALUE_PREFIXES;

  return reservedPrefixes.includes(firstWord) ? firstWord : undefined;
};
