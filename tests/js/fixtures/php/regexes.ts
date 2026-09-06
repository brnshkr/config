/* eslint-disable regexp/prefer-set-operation -- These patterns mirror PCRE, which has no set-operation syntax */
interface PhpRegex {
  file: string;
  php: string;
  regex: RegExp;
}

export const createPhpRegexes = (): PhpRegex[] => [
  /* eslint-disable no-control-regex, regexp/control-character-escape, regexp/no-control-character, unicorn/no-hex-escape -- PCRE spells no `\u{...}` escape and reads `\v` as a character class rather than a vertical tab, and matching control characters is what these three patterns are for */
  {
    file: 'conf/ai/mate/src/Support/Project.php',
    php: String.raw`/\x1B\[[\x30-\x3F]*[\x20-\x2F]*[\x40-\x7E]/`,
    regex: /\x1B\[[\x30-\x3F]*[\x20-\x2F]*[\x40-\x7E]/v,
  },
  {
    file: 'conf/ai/mate/src/Support/Project.php',
    php: String.raw`/\x1B\][^\x07\x1B]*(?:\x07|\x1B\\)/`,
    regex: /\x1B\][^\x07\x1B]*(?:\x07|\x1B\\)/v,
  },
  {
    file: 'conf/ai/mate/src/Support/Project.php',
    php: String.raw`/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/`,
    regex: /[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/v,
  },
  /* eslint-enable no-control-regex, regexp/control-character-escape, regexp/no-control-character, unicorn/no-hex-escape -- Restore rules */
  {
    file: 'conf/ai/mate/src/Tool/ProjectTool.php',
    php: String.raw`/^VERSION\s*[!+:?]*=\s*(?<version>[^\s#]+)/m`,
    regex: /^VERSION\s*[!+:?]*=\s*(?<version>[^\s#]+)/mv,
  },
  {
    file: 'conf/ai/mate/src/Tool/TestTool.php',
    php: String.raw`/\s+/`,
    regex: /\s+/v,
  },
  {
    file: 'src/php/ComposerJson.php',
    php: String.raw`/<|>=/`,
    regex: /<|>=/v,
  },
  {
    file: 'src/php/ComposerJson.php',
    php: String.raw`/^ {4,}/m`,
    regex: /^ {4,}/mv,
  },
  {
    file: 'src/php/Composer/Command/PrintModuleConfigCommand.php',
    php: String.raw`/^ +/m`,
    regex: /^ +/mv,
  },
  {
    file: 'src/php/PhpStan/Rule/BoolishPrefixRule.php',
    php: String.raw`/^(?:[0-9a-z]+|[0-9A-Z]+)/`,
    regex: /^(?:[0-9a-z]+|[0-9A-Z]+)/v,
  },
  {
    file: 'src/php/PhpStan/Rule/BoolishPrefixRule.php',
    php: String.raw`/^(?:as|to)(?:boolean|bool)/i`,
    regex: /^(?:as|to)(?:boolean|bool)/iv,
  },
  {
    file: 'src/php/PhpStan/Rule/InternalUsageRule.php',
    php: String.raw`/^[\w\\]+$/`,
    regex: /^[\w\\]+$/v,
  },
  {
    file: 'src/php/PhpStan/Rule/InternalUsageRule.php',
    php: String.raw`/^(?<delimiter>[^\w\\]).*\k<delimiter>[A-Za-z]*$/s`,
    regex: /^(?<delimiter>[^\w\\]).*\k<delimiter>[A-Za-z]*$/sv,
  },
  {
    file: 'src/php/PhpStan/Rule/InternalUsageRule.php',
    php: String.raw`/^[\w\\]/`,
    regex: /^[\w\\]/v,
  },
  {
    file: 'src/php/PhpStan/Rule/InternalUsageRule.php',
    php: String.raw`/\*\s+@internal(?=\s|$)(?<target>[^\n]*)(?:\n|$)/`,
    regex: /\*\s+@internal(?=\s|$)(?<target>[^\n]*)(?:\n|$)/v,
  },
  {
    file: 'src/php/PhpStan/Rule/PublicApiDocumentationRule.php',
    php: String.raw`/\/\*\*(?<body>.*?)(?:\n[\t ]*\*[\t ]+@|\*\/)/s`,
    regex: /\/\*\*(?<body>.*?)(?:\n[\t ]*\*[\t ]+@|\*\/)/sv,
  },
  {
    file: 'src/php/PhpStan/Rule/PublicApiDocumentationRule.php',
    php: String.raw`/^[\t ]*\*[\t ]+(?!@)[^\s*\/][^\n]*/m`,
    regex: /^[\t ]*\*[\t ]+(?!@)[^\s*\/][^\n]*/mv,
  },
  {
    file: 'src/php/PhpStan/Rule/PublicApiDocumentationRule.php',
    php: String.raw`/@param\s[^@]*?\$%s\b(?<description>[^\n]*)/`,
    regex: /@param\s[^@]*?\$paramName\b(?<description>[^\n]*)/v,
  },
  {
    file: 'src/php/PhpStan/Rule/PublicApiDocumentationRule.php',
    php: String.raw`/[A-Za-z]/`,
    regex: /[A-Za-z]/v,
  },
  {
    file: 'src/php/PhpStan/Rule/PublicApiDocumentationRule.php',
    php: String.raw`/@return\s+\S+\s+(?<description>\S[^\n]*)/`,
    regex: /@return\s+\S+\s+(?<description>\S[^\n]*)/v,
  },
  {
    file: 'src/php/PhpStan/Rule/ResolvableDocReferenceRule.php',
    php: String.raw`/(?<opening>\{@|\*\s+@)(?<tag>link|see)\s+(?<target>[^\s}]+)/`,
    regex: /(?<opening>\{@|\*\s+@)(?<tag>link|see)\s+(?<target>[^\s\}]+)/v,
  },
  {
    file: 'src/php/PhpStan/Rule/ResolvableDocReferenceRule.php',
    php: String.raw`/^\p{Lu}/`,
    regex: /^\p{Uppercase_Letter}/v,
  },
  {
    file: 'src/php/PhpStan/Rule/Trait/RuleTrait.php',
    php: String.raw`/\*\s+@`,
    regex: /\*\s+@api\b/v,
  },
  {
    file: 'src/php/Spelling.php',
    php: String.raw`/^(?<text>.*):(?<lineNumbers>\d+(?:,\d+)*)$/`,
    regex: /^(?<text>.*):(?<lineNumbers>\d+(?:,\d+)*)$/v,
  },
  {
    file: 'tests/php/CommandTest.php',
    php: String.raw`/^Running.+\n/`,
    regex: /^Running.+\n/v,
  },
];
/* eslint-enable regexp/prefer-set-operation -- Restore rule */
