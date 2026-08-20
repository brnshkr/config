/* eslint-disable regexp/prefer-set-operation -- These patterns mirror PCRE, which has no set-operation syntax */
interface PhpRegex {
  file: string;
  php: string;
  regex: RegExp;
}

export const createPhpRegexes = (): PhpRegex[] => [
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
    file: 'src/php/PhpStan/Rule/Trait/RuleTrait.php',
    php: String.raw`/\*\s+@`,
    regex: /\*\s+@api\b/v,
  },
  {
    file: 'tests/php/CommandTest.php',
    php: String.raw`/^Running.+\n/`,
    regex: /^Running.+\n/v,
  },
];
/* eslint-enable regexp/prefer-set-operation -- Restore rule */
