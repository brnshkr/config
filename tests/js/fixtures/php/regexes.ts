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
    file: 'src/php/PhpStan/Rule/PublicApiDocumentationRule.php',
    php: String.raw`/\*\s+@inheritDoc\b/i`,
    regex: /\*\s+@inheritDoc\b/iv,
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
    file: 'src/php/PhpStan/Rule/Trait/ArchitectureRuleTrait.php',
    php: String.raw`/.*%s$/`,
    regex: /.*%s$/v,
  },
  {
    file: 'src/php/PhpStan/Rule/Trait/ArchitectureRuleTrait.php',
    php: String.raw`/^%s\\%s\\.+$/`,
    regex: /^%s\\%s\\.+$/v,
  },
  {
    file: 'src/php/PhpStan/Rule/Trait/ArchitectureRuleTrait.php',
    php: String.raw`/^%s\\(?:%s)\\.+$/`,
    regex: /^%s\\(?:%s)\\.+$/v,
  },
  {
    file: 'src/php/PhpStan/Rule/Trait/ArchitectureRuleTrait.php',
    php: String.raw`/^%s\\(?:[^\\]+\\)*%s\\[^\\]+$/`,
    regex: /^%s\\(?:[^\\]+\\)*%s\\[^\\]+$/v,
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
    file: 'src/php/Str.php',
    php: String.raw`/^(?<delimiter>[^\w\\]).*\k<delimiter>[A-Za-z]*$/s`,
    regex: /^(?<delimiter>[^\w\\]).*\k<delimiter>[A-Za-z]*$/sv,
  },
  {
    file: 'src/php/TwigCsFixer.php',
    php: String.raw`/^%s\/[^\/]+\/[^\/]+\//`,
    regex: /^%s\/[^\/]+\/[^\/]+\//v,
  },
  {
    file: 'tests/php/CommandTest.php',
    php: String.raw`/^Running.+\n/`,
    regex: /^Running.+\n/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/(?<![\w.-])%s(?![\w.-])/`,
    regex: /(?<![\w.-])%s(?![\w.-])/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/^%s$/`,
    regex: /^%s$/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/PHP_STAN_CONFIG\s+\?=\s+\S+vendor\/brnshkr\/config\/conf\/phpstan\.dist\.php/`,
    regex: /PHP_STAN_CONFIG\s+\?=\s+\S+vendor\/brnshkr\/config\/conf\/phpstan\.dist\.php/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.dist\.php/`,
    regex: /PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.dist\.php/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.php/`,
    regex: /PHP_STAN_CONFIG\s+\?=\s+\S+conf\/phpstan\.php/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/SEMVER_REGEX\s+\?=\s+(?<grammar>\S+)/`,
    regex: /SEMVER_REGEX\s+\?=\s+(?<grammar>\S+)/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/^\.PHONY:(?!.* consumer-command ).* check /m`,
    regex: /^\.PHONY:(?!.* consumer-command ).* check /mv,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/^\.PHONY:.* consumer-command /m`,
    regex: /^\.PHONY:.* consumer-command /mv,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/^\.PHONY:\s*$/m`,
    regex: /^\.PHONY:\s*$/mv,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/--dry-run\n.*phpstan analyze.*\n.*phpunit/s`,
    regex: /--dry-run\n.*phpstan analyze.*\n.*phpunit/sv,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/php-cs-fixer fix [^\n]* -v\n.*--dry-run\n.*phpunit/s`,
    regex: /php-cs-fixer fix [^\n]* -v\n.*--dry-run\n.*phpunit/sv,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/rector done\n(?:.*\n)*php-cs-fixer fix/`,
    regex: /rector done\n(?:.*\n)*php-cs-fixer fix/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/php-cs-fixer fix [^\n]*--dry-run\nphp-cs-fixer done/`,
    regex: /php-cs-fixer fix [^\n]*--dry-run\nphp-cs-fixer done/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/rector process [^\n]*--dry-run\nrector done/`,
    regex: /rector process [^\n]*--dry-run\nrector done/v,
  },
  {
    file: 'tests/php/MakefileTest.php',
    php: String.raw`/phpstan analyze [^\n]*\nphpstan done/`,
    regex: /phpstan analyze [^\n]*\nphpstan done/v,
  },
];
/* eslint-enable regexp/prefer-set-operation -- Restore rule */
