<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\ComposerJson;
use Brnshkr\Config\Json;
use Brnshkr\Config\PhpStan\Rule\FileLevelDocCache;
use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;
use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use InvalidArgumentException;
use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<InternalUsageRule>
 */
#[CoversClass(InternalUsageRule::class)]
#[UsesClass(ComposerJson::class)]
#[UsesClass(FileLevelDocCache::class)]
#[UsesClass(Json::class)]
#[UsesClass(Str::class)]
final class InternalUsageRuleTest extends AbstractRuleTestCase
{
    private const string FIXTURE_DIRECTORY = __DIR__ . '/../../Fixtures/PhpStan/Rule';

    /**
     * @var ?array<array-key, non-empty-string|list<non-empty-string>>
     */
    private ?array $allowedInternals = null;

    /**
     * @var ?array<array-key, non-empty-string|list<non-empty-string>>
     */
    private ?array $allowedCallers = null;

    public function testRule(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/Internal/ScopedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeScopedInternalClass.php',
        );
    }

    public function testRuleReportsFreeFunctions(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalFunctions.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalFunction.php',
        );
    }

    public function testRuleReportsFromGlobalNamespace(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/Internal/ScopedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassFromGlobalNamespace.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeScopedInternalClassFromGlobalNamespace.php',
        );
    }

    public function testRuleComparesNamespacesBySubtree(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassFromNestedNamespace.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassFromSiblingNamespace.php',
        );
    }

    public function testRuleAllowsTheWholeOrganizationForAVendorTarget(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/OrganizationInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeOrganizationInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeOrganizationInternalClassFromLookalikeNamespace.php',
        );
    }

    public function testRuleTreatsADescribedTagAsABareInternal(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/DescribedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeDescribedInternalClass.php',
        );
    }

    public function testRuleIgnoresALeadingBackslashInTheTarget(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/BackslashTargetInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeBackslashTargetInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeBackslashTargetInternalClassFromOutside.php',
        );
    }

    public function testAFileLevelInternalTagPlacesTheCallerInsideThatSubtree(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassFromFileLevelInternal.php',
        );
    }

    public function testAFileLevelInternalTagIsFoundBelowDeclareToo(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassFromFileLevelInternalBelowDeclare.php',
        );
    }

    public function testAFileLevelInternalTagCoversAReturnedClosure(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassInsideReturnedClosure.php',
        );
    }

    public function testTheFileLevelTagWinsOverALaterBareInternal(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassWithTwoDocBlocks.php',
        );
    }

    public function testAFileLevelTagCoversAnImportedInternalFunction(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/Internal/InternalFunctionsScoped.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalFunctionLikeDocsConfig.php',
        );
    }

    public function testATagOnTheReturnPlacesTheCallerInsideThatSubtree(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassTaggedOnReturn.php',
        );
    }

    public function testAllowedCallersExemptsTheCaller(): void
    {
        $this->allowedCallers = ['External\AllowedConsumer'];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    public function testAMappedEntryAllowsOnlyTheTargetsItNames(): void
    {
        $this->allowedCallers = [
            'External\AllowedConsumer' => ['Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal'],
        ];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    public function testAMappedEntryStillReportsATargetItDoesNotName(): void
    {
        $this->allowedCallers = [
            'External\NarrowedConsumer' => ['Acme\Somewhere\Else'],
        ];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassOutsideAllowedTargets.php',
        );
    }

    public function testAMappedEntryMayNameTheSymbolItReaches(): void
    {
        $this->allowedCallers = [
            'External\AllowedConsumer' => [InternalClass::class],
        ];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    public function testAMappedEntryMayNameTheInternalTargetItReaches(): void
    {
        $this->allowedCallers = [
            'External\AllowedConsumer' => ['/^Brnshkr\\\Config$/'],
        ];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/ScopedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeScopedInternalClassAllowed.php',
        );
    }

    public function testABareEntryBesideAMappedOneKeepsItsOldMeaning(): void
    {
        $this->allowedCallers = [
            'External\AllowedConsumer',
            'External\NarrowedConsumer' => ['Acme\Somewhere\Else'],
        ];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassOutsideAllowedTargets.php',
        );
    }

    public function testRuleIgnoresALeadingBackslashInAnAllowListEntry(): void
    {
        $this->allowedCallers = ['\External\AllowedConsumer'];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    public function testAllowedInternalsMatchesTheDeclaringNamespace(): void
    {
        $this->allowedInternals = ['#^Brnshkr\\\Config\\\Tests\\\Fixtures#'];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    public function testAllowedInternalsMatchesTheInternalTarget(): void
    {
        $this->allowedInternals = ['/^Brnshkr\\\Config$/'];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/ScopedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeScopedInternalClassAllowed.php',
        );
    }

    public function testAllowedInternalsMatchesASymbolAndItsMembers(): void
    {
        $this->allowedInternals = [InternalClass::class . '::doSomething()', InternalClass::class];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    public function testRuleRejectsAnEmptyOptionEntry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Value for option "allowedInternals" must be a list of non-empty strings.');

        // @phpstan-ignore argument.type (Deliberately invalid input to cover the option validation)
        $this->createRule(['']);
    }

    public function testRuleRejectsAnUnterminatedRegexPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Entry "/^Brnshkr" for option "allowedCallers" is neither a namespace prefix nor a delimited regex pattern.');

        $this->createRule(null, ['/^Brnshkr']);
    }

    public function testRuleRejectsABackslashOnlyEntry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Entry "\" for option "allowedInternals" is neither a namespace prefix nor a delimited regex pattern.');

        $this->createRule(['\\']);
    }

    #[Override]
    protected function getRule(): Rule
    {
        return $this->createRule($this->allowedInternals, $this->allowedCallers);
    }

    /**
     * @param ?array<array-key, non-empty-string|list<non-empty-string>> $allowedInternals
     * @param ?array<array-key, non-empty-string|list<non-empty-string>> $allowedCallers
     */
    private function createRule(
        ?array $allowedInternals = null,
        ?array $allowedCallers = null,
    ): InternalUsageRule {
        return new InternalUsageRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            $allowedInternals,
            $allowedCallers,
        );
    }
}
