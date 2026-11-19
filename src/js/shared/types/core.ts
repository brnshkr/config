/**
 * @internal @brnshkr/config
 */

export type Maybe<TValue> = TValue | undefined;
export type Awaitable<TValue> = TValue | Promise<TValue>;
export type ValueOf<TObject> = TObject[keyof TObject];

export type Simplify<TValue> = {
  [TKey in keyof TValue]: TValue[TKey];
};
