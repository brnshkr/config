/**
 * @internal @brnshkr/config
 */

import type { Maybe, Simplify, ValueOf } from '#shared/types/core.ts';

export type AnyRecord = Record<PropertyKey, unknown>;
export type AnyObject<TObject = AnyRecord> = Simplify<Partial<Record<keyof TObject, ValueOf<TObject>>>>;
export type ObjectKeys<TObject = AnyRecord> = Simplify<(keyof TObject)[]>;
export type ObjectValues<TObject = AnyRecord> = Simplify<ValueOf<TObject>[]>;
export type ObjectEntries<TObject = AnyRecord> = Simplify<[keyof TObject, ValueOf<TObject>][]>;

export type ObjectFromEntries<TObjectEntries extends ObjectEntries> = Simplify<{
  [TEntry in TObjectEntries[number] as TEntry[0]]: TEntry[1];
}>;

interface ObjectKeysFunction {
  <TKey>(map: ReadonlyMap<TKey, unknown>): TKey[];
  <TObject extends AnyObject<TObject>>(object: TObject): ObjectKeys<TObject>;
}

interface ObjectValuesFunction {
  <TValue>(map: ReadonlyMap<unknown, TValue>): TValue[];
  <TObject extends AnyObject<TObject>>(object: TObject): ObjectValues<TObject>;
}

interface ObjectEntriesFunction {
  <TKey, TValue>(map: ReadonlyMap<TKey, TValue>): [TKey, TValue][];
  <TObject extends AnyObject<TObject>>(object: TObject): ObjectEntries<TObject>;
}

export const objectKeys = <ObjectKeysFunction>(
  (object: object): unknown[] => (object instanceof Map ? object.keys().toArray() : Object.keys(object))
);

export const objectValues = <ObjectValuesFunction>(
  (object: object): unknown[] => (object instanceof Map ? object.values().toArray() : Object.values(object))
);

export const objectEntries = <ObjectEntriesFunction>(
  (object: object): unknown[] => (object instanceof Map ? object.entries().toArray() : Object.entries(object))
);

interface ObjectFromEntriesFunction {
  <TObjectEntries extends ObjectEntries>(entries: TObjectEntries): ObjectFromEntries<TObjectEntries>;
  <TKey extends PropertyKey, TValue>(
    entries: ReadonlyMap<TKey, TValue>,
  ): string extends TKey ? Record<TKey, TValue> : Partial<Record<TKey, TValue>>;
}

export const objectFromEntries = <ObjectFromEntriesFunction>(
  (entries: Iterable<readonly [PropertyKey, unknown]>): AnyRecord => Object.fromEntries(entries)
);

export const objectFreeze = <TObject extends AnyObject<TObject>>(
  object: TObject,
): Simplify<Readonly<TObject>> => Object.freeze(object);

export const objectAssign = <TObject extends AnyObject<TObject>>(
  target: TObject,
  source: Partial<TObject>,
): TObject => Object.assign(target, source);

interface ReadOwnValueFunction {
  <TKey, TValue>(map: ReadonlyMap<TKey, TValue>, key: TKey): Maybe<TValue>;
  <TObject extends object, TKey extends keyof TObject>(object: TObject, key: TKey): Maybe<TObject[TKey]>;
}

interface WriteOwnValueFunction {
  <TKey, TValue>(map: Map<TKey, TValue>, key: TKey, value: TValue): void;
  <TObject extends object, TKey extends keyof TObject>(object: TObject, key: TKey, value: TObject[TKey]): void;
}

interface PickKeysFunction {
  <TKey, TValue>(map: ReadonlyMap<TKey, TValue>, keys: readonly TKey[]): Maybe<Map<TKey, TValue>>;
  <TObject extends AnyObject<TObject>, TKey extends keyof TObject>(
    object: TObject,
    keys: readonly TKey[],
  ): Maybe<Pick<TObject, TKey>>;
}

export const readOwnValue = <ReadOwnValueFunction>((object: object, key: PropertyKey): unknown => {
  if (object instanceof Map) {
    return object.get(key);
  }

  return Object.hasOwn(object, key) ? Reflect.get(object, key) : undefined;
});

export const writeOwnValue = <WriteOwnValueFunction>((object: object, key: PropertyKey, value: unknown): void => {
  if (object instanceof Map) {
    object.set(key, value);

    return;
  }

  Object.defineProperty(object, key, {
    configurable: true,
    enumerable: true,
    value,
    writable: true,
  });
});

export const pickKeys = <PickKeysFunction>((object: object, keys: readonly unknown[]): unknown => {
  const pickedEntries = (object instanceof Map ? object.entries().toArray() : Object.entries(object))
    .filter(([key]) => keys.includes(key));

  if (pickedEntries.length === 0) {
    return undefined;
  }

  return object instanceof Map ? new Map(pickedEntries) : Object.fromEntries(pickedEntries);
});

export const isPlainObject = (value: unknown): value is Record<string, unknown> => {
  if (typeof value !== 'object' || !value) {
    return false;
  }

  const prototype: unknown = Object.getPrototypeOf(value) ?? Object.prototype;

  return prototype === Object.prototype;
};
