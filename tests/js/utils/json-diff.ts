import { objectEntries, readOwnValue, writeOwnValue } from '#shared/utils/object.ts';

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

/**
 * @throws {TypeError}
 */
const isEqual = (value1: JsonValue, value2: JsonValue): boolean => JSON.stringify(value1) === JSON.stringify(value2);

const isPlainObject = (value: unknown): value is JsonObject => typeof value === 'object'
  && (value ?? undefined) !== undefined
  && !Array.isArray(value);

/**
 * @throws {TypeError}
 */
export const toJsonObject = (value: unknown): JsonObject => {
  if (!isPlainObject(value)) {
    throw new TypeError('Value must be a JSON object.');
  }

  return value;
};

/**
 * @throws {TypeError}
 */
const diffObjects = (object1: JsonObject, object2: JsonObject): ObjectDiff => {
  const addedValues: JsonObject = {};
  const removedKeys: string[] = [];
  const changedValues: JsonObject = {};

  for (const [object1Key, object1Value] of objectEntries(object1)) {
    const object2Value = readOwnValue(object2, object1Key);

    if (object2Value === undefined) {
      removedKeys.push(String(object1Key));
    } else if (!isEqual(object1Value, object2Value)) {
      writeOwnValue(changedValues, object1Key, object2Value);
    }
  }

  for (const [object2Key, object2Value] of objectEntries(object2)) {
    if (!Object.hasOwn(object1, object2Key)) {
      writeOwnValue(addedValues, object2Key, object2Value);
    }
  }

  return {
    addedValues,
    changedValues,
    removedKeys,
  };
};

/**
 * @throws {TypeError}
 */
export const computeConfigDiff = (object1: JsonObject, object2: JsonObject): ConfigDiff => {
  const diff: ConfigDiff = {};

  for (const [object1Key, object1Value] of objectEntries(object1)) {
    const object2Value = readOwnValue(object2, object1Key);

    if (object2Value === undefined) {
      diff[`-${String(object1Key)}`] = object1Value;

      continue;
    }

    if (isEqual(object1Value, object2Value)) {
      continue;
    }

    writeOwnValue(diff, String(object1Key), (isPlainObject(object1Value) && isPlainObject(object2Value))
      ? diffObjects(object1Value, object2Value)
      : object2Value);
  }

  for (const [object2Key, object2Value] of objectEntries(object2)) {
    if (!Object.hasOwn(object1, object2Key)) {
      diff[`+${String(object2Key)}`] = object2Value;
    }
  }

  return diff;
};
