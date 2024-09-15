import type { Config } from '../types/config';

export const css = (): Config[] => [
  {
    extends: 'stylelint-config-standard',
    reportDescriptionlessDisables: true,
    reportInvalidScopeDisables: true,
    reportNeedlessDisables: true,
    reportUnscopedDisables: true,
    rules: {
      'at-rule-disallowed-list': [
        'debug',
      ],
      'at-rule-property-required-list': {
        'font-face': [
          'font-display',
          'font-family',
          'font-style',
          'font-weight',
          'src',
        ],
      },
      'color-hex-length': 'long',
      'color-named': 'never',
      'color-no-invalid-hex': true,
      'comment-word-disallowed-list': [new RegExp(`^${['TO', 'DO'].join('')}`, 'v')],
      'declaration-no-important': true,
      'declaration-property-value-disallowed-list': {
        border: ['none'],
        'border-top': ['none'],
        'border-right': ['none'],
        'border-bottom': ['none'],
        'border-left': ['none'],
      },
      'declaration-property-unit-allowed-list': {
        '/^animation/': 'ms',
        '/^transition/': 'ms',
        'font-size': ['em', 'rem'],
        'line-height': [],
      },
      'font-family-name-quotes': 'always-unless-keyword',
      'font-weight-notation': 'numeric',
      'function-linear-gradient-no-nonstandard-direction': true,
      'function-no-unknown': true,
      'function-url-no-scheme-relative': true,
      'function-url-scheme-allowed-list': ['data', 'https'],
      'max-nesting-depth': 3,
      'no-unknown-animations': true,
      'no-unknown-custom-media': true,
      'no-unknown-custom-properties': true,
      'string-no-newline': true,
      'unit-no-unknown': true,
      'value-keyword-case': ['lower', {
        camelCaseSvgKeywords: true,
        ignoreKeywords: [/^geometricPrecision$/v],
      }],
    },
  },
];
