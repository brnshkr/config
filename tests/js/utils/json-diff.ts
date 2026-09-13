import { objectEntries } from '../../../src/js/shared/utils/object';

export type JsonValue = string | number | boolean | null | JsonValue[] | JsonObject;

export interface JsonObject {
  [key: string]: JsonValue;
}

export interface ObjectDiff {
  addedValues: JsonObject;
  changedValues: JsonObject;
  removedKeys: string[];
}

export type ConfigDiff = Record<string, JsonValue | ObjectDiff>;

const isEqual = (value1: JsonValue, value2: JsonValue): boolean => JSON.stringify(value1) === JSON.stringify(value2);

const isPlainObject = (value: JsonValue | undefined): value is JsonObject => typeof value === 'object'
  && (value ?? undefined) !== undefined
  && !Array.isArray(value);

const diffObjects = (object1: JsonObject, object2: JsonObject): ObjectDiff => {
  const addedValues: JsonObject = {};
  const removedKeys: string[] = [];
  const changedValues: JsonObject = {};

  for (const [object1Key, object1Value] of objectEntries(object1)) {
    if (Object.hasOwn(object2, object1Key)) {
      const object2Value = <JsonValue>object2[object1Key];

      if (!isEqual(object1Value, object2Value)) {
        changedValues[object1Key] = object2Value;
      }
    } else {
      removedKeys.push(String(object1Key));
    }
  }

  for (const [object2Key, object2Value] of objectEntries(object2)) {
    if (!Object.hasOwn(object1, object2Key)) {
      addedValues[object2Key] = object2Value;
    }
  }

  return {
    addedValues,
    changedValues,
    removedKeys,
  };
};

export const computeConfigDiff = (object1: JsonObject, object2: JsonObject): ConfigDiff => {
  const diff: ConfigDiff = {};

  for (const [object1Key, object1Value] of objectEntries(object1)) {
    if (!Object.hasOwn(object2, object1Key)) {
      diff[`-${String(object1Key)}`] = object1Value;

      continue;
    }

    const object2Value = <JsonValue>object2[object1Key];

    if (isEqual(object1Value, object2Value)) {
      continue;
    }

    diff[object1Key] = (isPlainObject(object1Value) && isPlainObject(object2Value))
      ? diffObjects(object1Value, object2Value)
      : object2Value;
  }

  for (const [object2Key, object2Value] of objectEntries(object2)) {
    if (!Object.hasOwn(object1, object2Key)) {
      diff[`+${String(object2Key)}`] = object2Value;
    }
  }

  return diff;
};
