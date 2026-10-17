/**
 * @internal @brnshkr/config/eslint
 */

import { MAIN_SCOPES, SUB_SCOPES } from '#eslint/types/scopes.ts';
import { ATTRIBUTE_GROUP_PREFIXES, ATTRIBUTE_ORDER, TRAILING_ATTRIBUTES } from '#eslint/utils/attributes.ts';
import { buildConfigName } from '#eslint/utils/config.ts';

import {
  GLOB_BLADE,
  GLOB_HANDLEBARS,
  GLOB_HTML_ERB,
  GLOB_HTML_FILES,
  GLOB_HTML_PHP,
  GLOB_HTML_TWIG,
  GLOB_NUNJUCKS,
  GLOB_PHP_INCLUDE,
  GLOB_PHTML,
  GLOB_SCRIPT_FILES,
} from '#eslint/utils/globs.ts';

import { MODULES, resolvePackages } from '#eslint/utils/module.ts';
import { INDENT, MAX_EMPTY_LINES, MAX_LEN } from '#shared/utils/constants.ts';
import { objectEntries, objectFromEntries, readOwnValue } from '#shared/utils/object.ts';

import type { Config } from '#eslint/types/config.ts';

const TEMPLATE_ENGINES = <const>{
  blade: {
    files: [
      GLOB_BLADE,
    ],
    syntax: {
      '{{': '}}',
      '{!!': '!!}',
    },
  },
  erb: {
    files: [
      GLOB_HTML_ERB,
    ],
    syntax: 'ERB',
  },
  handlebars: {
    files: [
      GLOB_HANDLEBARS,
    ],
    syntax: 'HANDLEBAR_EXTENDED',
  },
  nunjucks: {
    files: [
      GLOB_NUNJUCKS,
    ],
    syntax: 'NUNJUCKS',
  },
  php: {
    files: [
      GLOB_HTML_PHP,
      GLOB_PHP_INCLUDE,
      GLOB_PHTML,
    ],
    syntax: {
      '<?php': '?>',
      '<?=': '?>',
    },
  },
  twig: {
    files: [
      GLOB_HTML_TWIG,
    ],
    syntax: 'TWIG',
  },
};

const PREFORMATTED_TAGS = [
  'code',
  'pre',
];

const AUTOCOMPLETE_INPUT_TYPES = <const>[
  'date',
  'datetime-local',
  'email',
  'month',
  'number',
  'password',
  'search',
  'tel',
  'text',
  'time',
  'url',
  'week',
];

const AUTOCOMPLETE_TAGS = <const>[
  'select',
  'textarea',
];

const AUTOCOMPLETE_FIELD_NAMES_BY_TOKEN = <const>{
  'additional-name': [
    'additionalName',
    'middleInitial',
    'middleName',
  ],
  'address-level1': [
    'county',
    'province',
    'region',
    'state',
  ],
  'address-level2': [
    'city',
    'town',
  ],
  'address-level3': [
    'district',
    'suburb',
  ],
  'address-level4': [
    'neighborhood',
  ],
  'address-line1': [
    'address1',
    'addressLine1',
  ],
  'address-line2': [
    'address2',
    'addressLine2',
  ],
  'address-line3': [
    'address3',
    'addressLine3',
  ],
  bday: [
    'birthDate',
    'birthday',
    'dateOfBirth',
    'dob',
  ],
  'bday-day': [
    'birthDay',
  ],
  'bday-month': [
    'birthMonth',
  ],
  'bday-year': [
    'birthYear',
  ],
  'cc-additional-name': [
    'cardMiddleName',
  ],
  'cc-csc': [
    'cvc',
    'cvv',
    'securityCode',
  ],
  'cc-exp': [
    'cardExpiry',
    'expiry',
  ],
  'cc-exp-month': [
    'expiryMonth',
  ],
  'cc-exp-year': [
    'expiryYear',
  ],
  'cc-family-name': [
    'cardLastName',
  ],
  'cc-given-name': [
    'cardFirstName',
  ],
  'cc-name': [
    'cardName',
    'cardholderName',
  ],
  'cc-number': [
    'cardNumber',
    'ccNumber',
  ],
  'cc-type': [
    'cardType',
  ],
  country: [
    'country',
  ],
  'country-name': [
    'countryName',
  ],
  'current-password': [
    'currentPassword',
    'oldPassword',
  ],
  email: [
    'email',
    'emailAddress',
    'mail',
  ],
  'family-name': [
    'familyName',
    'lastName',
    'surname',
  ],
  'given-name': [
    'firstName',
    'forename',
    'givenName',
  ],
  'honorific-prefix': [
    'honorificPrefix',
    'salutation',
  ],
  'honorific-suffix': [
    'honorificSuffix',
    'suffix',
  ],
  impp: [
    'impp',
    'instantMessaging',
    'messenger',
  ],
  language: [
    'language',
    'preferredLanguage',
  ],
  name: [
    'fullName',
  ],
  'new-password': [
    'newPassword',
    'plainPassword',
  ],
  nickname: [
    'nickName',
    'nickname',
  ],
  'one-time-code': [
    'oneTimeCode',
    'otp',
  ],
  organization: [
    'company',
    'organization',
  ],
  'organization-title': [
    'jobTitle',
    'organizationTitle',
  ],
  photo: [
    'avatar',
    'photo',
    'profilePicture',
  ],
  'postal-code': [
    'postalCode',
    'postcode',
    'zip',
    'zipCode',
    'zipcode',
  ],
  sex: [
    'gender',
    'sex',
  ],
  'street-address': [
    'street',
    'streetAddress',
  ],
  tel: [
    'cellPhone',
    'mobile',
    'mobilePhone',
    'phone',
    'phoneNumber',
    'telephone',
  ],
  'tel-area-code': [
    'areaCode',
  ],
  'tel-country-code': [
    'dialCode',
    'phoneCountryCode',
  ],
  'tel-extension': [
    'extension',
    'phoneExtension',
  ],
  'tel-local': [
    'localNumber',
  ],
  'tel-national': [
    'nationalNumber',
  ],
  'transaction-amount': [
    'amount',
    'transactionAmount',
  ],
  'transaction-currency': [
    'currency',
    'transactionCurrency',
  ],
  url: [
    'homepage',
    'website',
  ],
  username: [
    'userName',
    'username',
  ],
};

const convertToSnakeCase = (name: string): string => name
  .replaceAll(/(?<=[\da-z])(?=[A-Z])/gv, '_')
  .toLowerCase();

const AUTOCOMPLETE_FIELD_NAMES = objectFromEntries(objectEntries(AUTOCOMPLETE_FIELD_NAMES_BY_TOKEN)
  .map(([token, fieldNames]) => [
    token,
    [...new Set(fieldNames.flatMap((fieldName) => [
      fieldName,
      convertToSnakeCase(fieldName),
    ]))],
  ]));

const AUTOCOMPLETE_FIELD_TAGS = <const>[
  'input',
  ...AUTOCOMPLETE_TAGS,
];

const LAZY_LOADED_TAGS = <const>[
  'iframe',
  'img',
];

const AUTOPLAY_ATTRIBUTES = <const>[
  'muted',
  'playsinline',
];

const REQUIRED_ATTRIBUTES = [
  {
    tag: 'a',
    attr: 'href',
    conditions: [
      {
        attr: 'download',
        kind: <const>'present',
      },
    ],
  },
  {
    tag: 'abbr',
    attr: 'title',
  },
  {
    tag: 'iframe',
    attr: 'sandbox',
  },
  {
    tag: 'img',
    attr: 'decoding',
  },
  {
    tag: 'img',
    attr: 'sizes',
    conditions: [
      {
        attr: 'srcset',
        kind: <const>'present',
      },
    ],
  },
  {
    tag: 'input',
    attr: 'accept',
    conditions: [
      {
        attr: 'type',
        kind: <const>'equal',
        value: 'file',
      },
    ],
  },
  {
    tag: 'input',
    attr: 'autocomplete',
    conditions: [
      {
        attr: 'type',
        kind: <const>'absent',
      },
    ],
  },
  ...AUTOCOMPLETE_INPUT_TYPES.map((type) => ({
    tag: 'input',
    attr: 'autocomplete',
    conditions: [
      {
        attr: 'type',
        kind: <const>'equal',
        value: type,
      },
    ],
  })),
  ...AUTOCOMPLETE_TAGS.map((tag) => ({
    tag,
    attr: 'autocomplete',
  })),
  ...objectEntries(AUTOCOMPLETE_FIELD_NAMES).flatMap(([token, fieldNames]) => fieldNames
    .flatMap((fieldName) => AUTOCOMPLETE_FIELD_TAGS.map((tag) => ({
      tag,
      attr: 'autocomplete',
      value: token,
      conditions: [
        {
          attr: 'name',
          kind: <const>'equal',
          value: fieldName,
        },
      ],
    })))),
  {
    tag: 'input',
    attr: 'inputmode',
    conditions: [
      {
        attr: 'type',
        kind: <const>'equal',
        value: 'number',
      },
    ],
  },
  {
    tag: 'link',
    attr: 'as',
    conditions: [
      {
        attr: 'rel',
        kind: <const>'equal',
        value: 'preload',
      },
    ],
  },
  {
    tag: 'link',
    attr: 'crossorigin',
    conditions: [
      {
        attr: 'rel',
        kind: <const>'equal',
        value: 'preload',
      },
      {
        attr: 'as',
        kind: <const>'equal',
        value: 'font',
      },
    ],
  },
  {
    tag: 'link',
    attr: 'type',
    conditions: [
      {
        attr: 'rel',
        kind: <const>'equal',
        value: 'icon',
      },
    ],
  },
  ...LAZY_LOADED_TAGS.map((tag) => ({
    tag,
    attr: 'loading',
  })),
  {
    tag: 'track',
    attr: 'kind',
  },
  {
    tag: 'track',
    attr: 'srclang',
    conditions: [
      {
        attr: 'kind',
        kind: <const>'equal',
        value: 'subtitles',
      },
    ],
  },
  ...AUTOPLAY_ATTRIBUTES.map((attribute) => ({
    tag: 'video',
    attr: attribute,
    conditions: [
      {
        attr: 'autoplay',
        kind: <const>'present',
      },
    ],
  })),
];

const RESTRICTED_ATTRIBUTES = [
  {
    tagPatterns: [
      '.*',
    ],
    attrPatterns: [
      '^on',
      '^(?:autofocus|contenteditable|srcdoc)$',
    ],
  },
];

const RESTRICTED_ATTRIBUTE_VALUES = [
  {
    attrPatterns: [
      '^(?:action|formaction|href|src)$',
    ],
    attrValuePatterns: [
      String.raw`^\s*javascript:`,
    ],
  },
  {
    attrPatterns: [
      '^href$',
    ],
    attrValuePatterns: [
      '^#$',
    ],
  },
  {
    attrPatterns: [
      '^sandbox$',
    ],
    attrValuePatterns: [
      String.raw`^(?=.*\ballow-scripts\b)(?=.*\ballow-same-origin\b)`,
    ],
  },
  {
    attrPatterns: [
      '^target$',
    ],
    attrValuePatterns: [
      '^_(?:parent|self|top)$',
    ],
  },
  {
    attrPatterns: [
      '^autocomplete$',
    ],
    attrValuePatterns: [
      '^off$',
    ],
  },
];

const ATTRIBUTE_PRIORITY = [
  ...ATTRIBUTE_ORDER,
  {
    pattern: `^(?!${[...TRAILING_ATTRIBUTES.map((attribute) => `${attribute}$`), ...ATTRIBUTE_GROUP_PREFIXES].join('|')})`,
    order: <const>'alphabetically',
  },
  ...TRAILING_ATTRIBUTES,
  ...ATTRIBUTE_GROUP_PREFIXES.map((prefix) => ({
    pattern: `^${prefix}`,
    order: <const>'alphabetically',
  })),
];

const HTML_EXTENSIONS = <const>[
  '.erb',
  '.handlebars',
  '.hbs',
  '.htm',
  '.html',
  '.njk',
  '.nunjucks',
  '.php',
  '.phtml',
  '.twig',
] satisfies string[];

export const html = async (): Promise<Config[]> => {
  const {
    requiredAll: [pluginHtml],
    optional: [pluginHtmlScripts],
  } = await resolvePackages(MODULES.html);

  if (!pluginHtml) {
    return [];
  }

  const { languageOptions } = pluginHtml.configs['flat/recommended'];

  return [
    {
      name: buildConfigName(MAIN_SCOPES.HTML, SUB_SCOPES.SETUP),
      plugins: {
        html: pluginHtml,
        ...(pluginHtmlScripts
          ? {
            'html-scripts': pluginHtmlScripts,
          }
          : undefined),
      },
      settings: {
        'html/html-extensions': HTML_EXTENSIONS,
      },
    },
    {
      name: buildConfigName(MAIN_SCOPES.HTML, SUB_SCOPES.PARSER),
      files: GLOB_HTML_FILES,
      languageOptions,
    },
    ...objectEntries(TEMPLATE_ENGINES).map(([engine, { files, syntax }]) => ({
      name: buildConfigName(MAIN_SCOPES.HTML, `${SUB_SCOPES.PARSER}-${engine}`),
      files: [...files],
      languageOptions: {
        parserOptions: {
          templateEngineSyntax: typeof syntax === 'string'
            ? readOwnValue(languageOptions.parser.TEMPLATE_ENGINE_SYNTAX, syntax)
            : syntax,
        },
      },
    })),
    {
      name: buildConfigName(MAIN_SCOPES.HTML, SUB_SCOPES.RULES),
      files: [
        ...GLOB_HTML_FILES,
        ...GLOB_SCRIPT_FILES,
      ],
      rules: {
        ...pluginHtml.configs.recommended.rules,
        'html/attrs-newline': ['error', {
          ifAttrsMoreThan: 3,
          inline: ['$inline'],
          maxLen: MAX_LEN,
          skip: PREFORMATTED_TAGS,
        }],
        'html/class-spacing': 'error',
        'html/css-no-empty-blocks': 'error',
        'html/element-newline': ['error', {
          inline: ['$inline'],
          skip: PREFORMATTED_TAGS,
        }],
        'html/head-order': 'error',
        'html/id-naming-convention': ['error', 'regex', {
          pattern: String.raw`^[a-z][\dA-Za-z]*(?:_+[\dA-Za-z]+)*_*$`,
          flags: 'v',
        }],
        'html/indent': ['error', INDENT],
        'html/lowercase': 'error',
        'html/max-element-depth': ['error', {
          max: 24,
        }],
        'html/no-abstract-roles': 'error',
        'html/no-accesskey-attrs': 'error',
        'html/no-aria-hidden-body': 'error',
        'html/no-aria-hidden-on-focusable': 'error',
        'html/no-duplicate-class': 'error',
        'html/no-empty-headings': 'error',
        'html/no-extra-spacing-tags': ['error', {
          disallowInAssignment: true,
          disallowMissing: true,
          disallowTabs: true,
        }],
        'html/no-extra-spacing-text': ['error', {
          skip: PREFORMATTED_TAGS,
        }],
        'html/no-heading-inside-button': 'error',
        'html/no-ineffective-attrs': 'error',
        'html/no-inline-styles': ['error', {
          allowExpressions: true,
        }],
        'html/no-invalid-attr-value': 'error',
        'html/no-invalid-entity': 'error',
        'html/no-invalid-role': 'error',
        'html/no-multiple-empty-lines': ['error', {
          max: MAX_EMPTY_LINES,
        }],
        'html/no-nested-interactive': 'error',
        'html/no-non-scalable-viewport': 'error',
        'html/no-positive-tabindex': 'error',
        'html/no-redundant-role': 'error',
        'html/no-restricted-attr-values': [
          'error',
          ...RESTRICTED_ATTRIBUTE_VALUES,
        ],
        'html/no-restricted-attrs': [
          'error',
          ...RESTRICTED_ATTRIBUTES,
        ],
        'html/no-restricted-tags': ['error', {
          tagPatterns: [
            '^[bisu]$',
          ],
        }],
        'html/no-script-style-type': 'error',
        'html/no-skip-heading-levels': 'error',
        'html/no-target-blank': 'error',
        'html/no-trailing-spaces': 'error',
        'html/no-whitespace-only-children': 'error',
        'html/prefer-https': 'error',
        'html/quotes': ['error', 'double', {
          enforceTemplatedAttrValue: true,
        }],
        'html/require-attrs': [
          'error',
          ...REQUIRED_ATTRIBUTES,
        ],
        'html/require-button-type': 'error',
        'html/require-content': 'error',
        'html/require-details-summary': 'error',
        'html/require-explicit-size': 'error',
        'html/require-form-method': 'error',
        'html/require-frame-title': 'error',
        'html/require-input-label': 'error',
        'html/require-meta-charset': 'error',
        'html/require-meta-description': 'error',
        'html/require-meta-viewport': 'error',
        'html/require-open-graph-protocol': 'error',
        'html/sort-attrs': ['error', {
          priority: ATTRIBUTE_PRIORITY,
        }],
        'html/svg-require-viewbox': 'error',
      },
    },
  ];
};
