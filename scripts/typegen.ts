import { file, write } from 'bun';

import { builtinRules } from 'eslint/use-at-your-own-risk';
import { flatConfigsToRulesDTS } from 'eslint-typegen/core';
import { compile } from 'json-schema-to-typescript';

import { getConfig } from '../src/js/eslint';
import { objectEntries, objectFromEntries } from '../src/js/shared/utils/object';

import type { JSONSchema } from 'json-schema-to-typescript';
import type { Config } from '../src/js/eslint/types/config';

const RULES_SCHEMA_REF = 'https://raw.githubusercontent.com/DavidAnson/markdownlint';
const COMBINE_STRATEGIES = <const>['merge', 'replace'];

type JsonValue = boolean | number | string | null | JsonValue[] | { [key: string]: JsonValue };
type JsonRecord = Record<string, JsonValue>;

const generateEsLintTypes = async (): Promise<void> => {
  const allConfigs: Config[] = [
    {
      plugins: {
        '': {
          // eslint-disable-next-line ts/no-deprecated -- See: https://github.com/eslint/eslint/issues/18322#issuecomment-2053615962
          rules: Object.fromEntries(builtinRules.entries()),
        },
      },
    },
    ...await getConfig().toConfigs(),
  ];

  const configNames = allConfigs.map(({ name }) => name).filter(Boolean);

  const dts = `${
    await flatConfigsToRulesDTS(allConfigs, {
      includeAugmentation: false,
      includeIgnoreComments: false,
    })
  }

  export type ConfigNames = "${configNames.join('" | "')}"
  `;

  await write('src/js/eslint/types/declarations/typegen.d.ts', dts);
};

const omitEntry = (
  object: JsonRecord,
  key: string,
): [string, JsonValue][] => objectEntries(object).filter(([entryKey]) => entryKey !== key);

const readSchema = async (path: string): Promise<JSONSchema> => {
  const schema = <JSONSchema>(await file(path).json());

  return objectFromEntries(objectEntries(schema).filter(([key]) => key !== '$id' && key !== 'title'));
};

const prepareSchema = (node: JsonValue): JSONSchema => {
  if (Array.isArray(node)) {
    return node.map((childNode) => prepareSchema(childNode));
  }

  if (typeof node !== 'object' || node === null) {
    return <JSONSchema><unknown>node;
  }

  const entries = objectEntries(node);

  if (entries.some(([key, value]) => key === '$ref' && typeof value === 'string' && value.startsWith(RULES_SCHEMA_REF))) {
    return {
      $ref: '#/definitions/Rules',
    };
  }

  // NOTICE: a minimum item count compiles to a tuple, which no config section can be merged into
  const prepared: JsonRecord = objectFromEntries(
    entries.filter(([key]) => key !== 'minItems').map(([key, value]) => [key, prepareSchema(value)]),
  );

  if (prepared['pattern'] === 'merge|replace') {
    return objectFromEntries([
      ...omitEntry(prepared, 'pattern'),
      ['enum', [...COMBINE_STRATEGIES]],
    ]);
  }

  const { required } = prepared;

  if (Array.isArray(required)) {
    return objectFromEntries([
      ...omitEntry(prepared, 'required'),
      ['required', required.filter((name) => name !== 'combine')],
    ]);
  }

  return prepared;
};

const generateMarkdownlintTypes = async (): Promise<void> => {
  const rulesSchema = await readSchema('node_modules/markdownlint/schema/markdownlint-config-schema-strict.json');
  const cli2Schema = await readSchema('node_modules/markdownlint-cli2/schema/markdownlint-cli2-config-schema.json');

  await write('src/js/markdownlint/types/declarations/typegen.d.ts', await compile(
    {
      ...prepareSchema(cli2Schema),
      definitions: {
        /* eslint-disable-next-line ts/naming-convention -- Key needs to be cased like this */
        Rules: rulesSchema,
      },
    },
    'Config',
    {
      additionalProperties: false,
      bannerComment: '',
      style: {
        singleQuote: true,
      },
    },
  ));
};

await generateEsLintTypes();
await generateMarkdownlintTypes();
