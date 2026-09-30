<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule\Trait;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Exception\UnreachableException;
use Brnshkr\Config\PhpStan\Rule\FileLevelDocCache;
use Brnshkr\Config\Str;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\Attribute;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use RuntimeException;

use function array_any;
use function is_int;
use function lcfirst;
use function sprintf;

/**
 * @internal
 *
 * @phpstan-type DocTag array{
 *     kind: 'param'|'property'|'return'|'template'|'throws',
 *     tag: string,
 *     name: string,
 *     description: string,
 *     line: int,
 * }
 */
trait RuleTrait
{
    protected const string KIND_CLASS       = 'Class';
    protected const string KIND_CONSTANT    = 'Constant';
    protected const string KIND_CONSTRUCTOR = 'Constructor';
    protected const string KIND_ENUM        = 'Enum';
    protected const string KIND_FUNCTION    = 'Function';
    protected const string KIND_INTERFACE   = 'Interface';
    protected const string KIND_METHOD      = 'Method';
    protected const string KIND_PARAMETER   = 'Parameter';
    protected const string KIND_PROPERTY    = 'Property';
    protected const string KIND_TRAIT       = 'Trait';
    protected const string KIND_VARIABLE    = 'Variable';

    protected const string TAG_API                = 'api';
    protected const string TAG_INTERNAL           = 'internal';
    protected const string TAG_NAMED_ARGUMENTS    = 'named-arguments';
    protected const string TAG_NO_NAMED_ARGUMENTS = 'no-named-arguments';

    /**
     * @return list<DocTag>
     */
    private static function getDocTags(string $docText): array
    {
        if (!Str::startsWith($docText, '/**')) {
            return [];
        }

        $parserConfig    = new ParserConfig(['lines' => true]);
        $constExprParser = new ConstExprParser($parserConfig);
        $phpDocParser    = new PhpDocParser($parserConfig, new TypeParser($parserConfig, $constExprParser), $constExprParser);
        $tags            = [];

        foreach ($phpDocParser->parse(new TokenIterator(new Lexer($parserConfig)->tokenize($docText)))->getTags() as $phpDocTagNode) {
            $value     = $phpDocTagNode->value;
            $startLine = $phpDocTagNode->getAttribute(Attribute::START_LINE);

            $describedTag = match (true) {
                $value instanceof ParamTagValueNode,
                $value instanceof TypelessParamTagValueNode => ['param', $value->parameterName, $value->description],
                $value instanceof PropertyTagValueNode      => ['property', $value->propertyName, $value->description],
                $value instanceof TemplateTagValueNode      => ['template', $value->name, $value->description],
                $value instanceof ReturnTagValueNode        => ['return', '', $value->description],
                $value instanceof ThrowsTagValueNode        => ['throws', '', $value->description],
                default                                     => null,
            };

            if ($describedTag === null) {
                continue;
            }

            $tags[] = [
                'kind'        => $describedTag[0],
                'tag'         => $phpDocTagNode->name,
                'name'        => $describedTag[1],
                'description' => Str::trim($describedTag[2]),
                'line'        => is_int($startLine) ? $startLine : 1,
            ];
        }

        return $tags;
    }

    private static function buildRuleError(string $message, int $line, bool $isIgnorable = true): IdentifierRuleError
    {
        $className = Str::getClassShortName(self::class);

        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/string here to keep package as lightweight as possible)
        $ruleName = lcfirst(Str::trimSuffix($className, 'Rule'));

        try {
            $organization = ComposerJson::forThisLibrary()->getPackageOrganization();
        } catch (RuntimeException $runtimeException) {
            throw UnreachableException::wrap($runtimeException);
        }

        $identifier = sprintf('%s.%s', $organization, $ruleName);

        $ruleErrorBuilder = RuleErrorBuilder::message($message)
            ->identifier($identifier)
            ->line($line)
        ;

        if (!$isIgnorable) {
            $ruleErrorBuilder->nonIgnorable();
        }

        return $ruleErrorBuilder->build();
    }

    private static function getClassLikeName(ClassLike $classLike): string
    {
        return $classLike->name?->toString() ?: '<unknown>';
    }

    /**
     * @return self::KIND_CLASS|self::KIND_ENUM|self::KIND_INTERFACE|self::KIND_TRAIT
     */
    private static function getKindForClassLike(ClassLike $classLike): string
    {
        return match (true) {
            $classLike instanceof Enum_      => self::KIND_ENUM,
            $classLike instanceof Interface_ => self::KIND_INTERFACE,
            $classLike instanceof Trait_     => self::KIND_TRAIT,
            default                          => self::KIND_CLASS,
        };
    }

    /**
     * @return self::KIND_CLASS|self::KIND_ENUM|self::KIND_INTERFACE|self::KIND_TRAIT
     */
    private static function getKindForClassReflection(ClassReflection $classReflection): string
    {
        return match (true) {
            $classReflection->isEnum()      => self::KIND_ENUM,
            $classReflection->isInterface() => self::KIND_INTERFACE,
            $classReflection->isTrait()     => self::KIND_TRAIT,
            default                         => self::KIND_CLASS,
        };
    }

    private static function isAnonymousClass(ClassLike $classLike): bool
    {
        return $classLike instanceof Class_ && $classLike->isAnonymous();
    }

    private static function hasTag(?Doc $doc, string $tag): bool
    {
        return self::hasTagInText($doc?->getText() ?? '', $tag);
    }

    private static function hasNamedArgumentsStance(?Doc $doc): bool
    {
        if (self::hasTag($doc, self::TAG_INTERNAL)) {
            return true;
        }

        if (self::hasTag($doc, self::TAG_NO_NAMED_ARGUMENTS)) {
            return true;
        }

        return self::hasTag($doc, self::TAG_NAMED_ARGUMENTS);
    }

    /**
     * @param callable(?Doc $doc): bool $declaresStance
     */
    private static function hasUngovernedMethod(ClassLike $classLike, callable $declaresStance): bool
    {
        return array_any(
            $classLike->getMethods(),
            static fn (ClassMethod $classMethod): bool => !$classMethod->isPrivate()
                && $classMethod->getParams() !== []
                && !$declaresStance($classMethod->getDocComment()),
        );
    }

    private static function hasClassTag(ClassReflection $classReflection, string $tag): bool
    {
        return self::hasTagInText($classReflection->getNativeReflection()->getDocComment() ?: '', $tag);
    }

    private static function hasTagInText(string $text, string $tag): bool
    {
        return Str::match($text, '/\*\s+@' . $tag . '\b/') !== [];
    }

    private static function resolveFileLevelDoc(Node $node, Scope $scope): ?Doc
    {
        $filePath = $scope->getFile();

        if (Str::isEmpty($filePath)) {
            return null;
        }

        if (!$scope->isInClass() && !$scope->getFunction() instanceof PhpFunctionFromParserNodeReflection) {
            FileLevelDocCache::captureFrom($node, $filePath);
        }

        return FileLevelDocCache::get($filePath);
    }

    /**
     * @return self::TAG_API|self::TAG_INTERNAL|null
     */
    private static function getVisibilityTag(?Doc $doc): ?string
    {
        return match (true) {
            self::hasTag($doc, self::TAG_INTERNAL) => self::TAG_INTERNAL,
            self::hasTag($doc, self::TAG_API)      => self::TAG_API,
            default                                => null,
        };
    }

    private static function hasConflictingVisibilityTags(?Doc $doc): bool
    {
        return self::hasTag($doc, self::TAG_API) && self::hasTag($doc, self::TAG_INTERNAL);
    }

    /**
     * @return self::TAG_API|self::TAG_INTERNAL|null
     */
    private static function getEffectiveVisibilityTag(?Doc $symbolDoc, ?Doc $fileDoc): ?string
    {
        return self::getVisibilityTag($symbolDoc) ?? self::getVisibilityTag($fileDoc);
    }
}
