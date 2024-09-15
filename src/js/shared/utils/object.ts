import type { ArrayElement, Simplify, ValueOf } from 'type-fest';

export type AnyRecord = Record<PropertyKey, unknown>;
export type AnyObject<TObject = AnyRecord> = Simplify<Partial<Record<keyof TObject, ValueOf<TObject>>>>;
export type ObjectKeys<TObject = AnyRecord> = Simplify<(keyof TObject)[]>;
export type ObjectValues<TObject = AnyRecord> = Simplify<ValueOf<TObject>[]>;
export type ObjectEntries<TObject = AnyRecord> = Simplify<[keyof TObject, ValueOf<TObject>][]>;

export type ObjectFromEntries<TObjectEntries extends ObjectEntries> = Simplify<{
  [TEntry in ArrayElement<TObjectEntries> as TEntry[0]]: TEntry[1];
}>;

export const objectKeys = <TObject extends AnyObject<TObject>>(
  object: TObject,
): ObjectKeys<TObject> => <ObjectKeys<TObject>>Object.keys(object);

export const objectValues = <TObject extends AnyObject<TObject>>(
  object: TObject,
): ObjectValues<TObject> => <ObjectValues<TObject>>Object.values(object);

export const objectEntries = <TObject extends AnyObject<TObject>>(
  object: TObject,
): ObjectEntries<TObject> => <ObjectEntries<TObject>>Object.entries(object);

export const objectFromEntries = <TObjectEntries extends ObjectEntries>(
  entries: TObjectEntries,
): ObjectFromEntries<TObjectEntries> => <ObjectFromEntries<TObjectEntries>>Object.fromEntries(entries);

export const objectFreeze = <TObject extends AnyObject<TObject>>(
  object: TObject,
): Simplify<Readonly<TObject>> => Object.freeze(object);

export const objectAssign = <TObject extends AnyObject<TObject>>(
  target: TObject,
  source: Partial<TObject>,
): TObject => Object.assign(target, source);
