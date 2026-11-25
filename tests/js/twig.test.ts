import path from 'node:path';

import { ESLint } from 'eslint';

import {
  expect,
  onTestFinished,
  test,
  vi,
} from 'vitest';

import { createTwigProcessor, getTwigFunctions } from '#eslint/configs/twig.ts';
import { getConfig } from '#eslint/index.ts';
import { objectEntries, objectFromEntries } from '#shared/utils/object.ts';

import type { Linter } from 'eslint';
import type { TwigFunction } from '#eslint/types/options.ts';

interface Scenario {
  readonly template: string;
}

interface ScenarioResult {
  readonly block: string | undefined;
  readonly messages: Pick<Linter.LintMessage, 'column' | 'line' | 'message' | 'ruleId'>[] | undefined;
  readonly output: string | undefined;
}

const TEMPLATE_PATH = path.join(process.cwd(), 'templates/acme-card.html.twig');
const LONG_CLASSES = 'flex items-center justify-between gap-4 rounded-lg border p-4 text-sm font-medium shadow-sm transition-colors';

const PROJECT_FUNCTIONS = [
  {
    name: 'acme_attrs',
    match: [
      {
        type: 'objectValues',
        path: '^classes$',
      },
    ],
  },
] satisfies TwigFunction[];

const SCENARIOS = {
  'attrs() with a short ternary': {
    template: '<div {{ attrs({ id: \'acme-card\', class: [\'flex\', is_active ? \'acme-unknown\'] }) }}></div>\n',
  },
  'attrs() with a full ternary': {
    template: '<div {{ attrs({ class: is_active ? \'acme-on\' : \'acme-off\', title: \'Acme\' }) }}></div>\n',
  },
  'attrs() with unsorted classes': {
    template: '<div {{ attrs({ class: [\'p-4  flex\', is_active ? \'hidden\'] }) }}></div>\n',
  },
  'attributes.defaults() with a concatenation': {
    template: '<div {{ attributes.defaults({ class: \'p-\' ~ size ~ \' acme-card\' }) }}></div>\n',
  },
  'html_classes() with strings and hash keys': {
    template: '{{ html_classes(\'acme-one\', { \'acme-two\': is_active }, x ?: \'grid\') }}\n',
  },
  'html_cva() with named arguments': {
    template: '{% set button = html_cva(base: \'acme-base\', variants: { size: { sm: \'acme-small\' } }) %}\n',
  },
  'a project function under its own key': {
    template: '{{ acme_attrs({ classes: [\'acme-one\'], title: \'acme-two\' }) }}\n',
  },
  'a class string spanning lines': {
    template: '<div {{ attrs({ class: \'\n  flex acme-unknown\n\' }) }}></div>\n',
  },
  'a long class string on a line of its own': {
    template: `<div {{ attrs({\n  class: [\n    '${LONG_CLASSES} duration-150',\n    'hidden',\n  ],\n}) }}></div>\n`,
  },
  'a long class string sharing its line': {
    template: `<div {{ attrs({ class: '${LONG_CLASSES}' }) }}></div>\n`,
  },
  'no twig function': {
    template: '<div class="flex"></div>\n',
  },
} satisfies Record<string, Scenario>;

const createLinter = async (isFixing: boolean): Promise<ESLint> => new ESLint({
  fix: isFixing,
  overrideConfigFile: true,
  overrideConfig: await getConfig({
    tailwind: {
      entryPoint: './conf/tailwind.css',
      twigFunctions: PROJECT_FUNCTIONS,
    },
  }).toConfigs(),
});

test('rejects a twig function path that backtracks super-linearly', () => {
  expect(() => getTwigFunctions([
    {
      name: 'acme_attrs',
      match: [
        {
          type: 'objectValues',
          path: '^(a+)+$',
        },
      ],
    },
  ])).toThrow('The configured pattern "^(a+)+$" can backtrack super-linearly.');
});

test('rejects a twig function name that is no identifier', () => {
  expect(() => getTwigFunctions([
    {
      name: 'acme-attrs',
      match: [
        {
          type: 'strings',
        },
      ],
    },
  ])).toThrow('Invalid Twig function name "acme-attrs".');
});

test('twig function classes', async () => {
  vi.stubEnv('CI', '');

  onTestFinished(() => {
    vi.unstubAllEnvs();
  });

  const [linter, fixingLinter] = await Promise.all([
    createLinter(false),
    createLinter(true),
  ]);

  const processor = createTwigProcessor(getTwigFunctions(PROJECT_FUNCTIONS));

  const lintScenario = async (name: string, scenario: Scenario): Promise<[string, ScenarioResult]> => {
    const [, block] = processor.preprocess?.(scenario.template, TEMPLATE_PATH) ?? [];

    const [[result], [fixedResult]] = await Promise.all([
      linter.lintText(scenario.template, {
        filePath: TEMPLATE_PATH,
      }),
      fixingLinter.lintText(scenario.template, {
        filePath: TEMPLATE_PATH,
      }),
    ]);

    return [
      name,
      {
        block: typeof block === 'object'
          ? block.text
          : undefined,
        messages: result?.messages
          .filter((message) => message.ruleId?.startsWith('tailwind/') === true)
          .map((message) => ({
            line: message.line,
            column: message.column,
            ruleId: message.ruleId,
            message: message.message,
          })),
        output: fixedResult?.output,
      },
    ];
  };

  const results = await Promise.all(objectEntries(SCENARIOS).map(async ([name, scenario]) => lintScenario(
    name,
    scenario,
  )));

  expect(objectFromEntries(results)).toMatchSnapshot();
});
