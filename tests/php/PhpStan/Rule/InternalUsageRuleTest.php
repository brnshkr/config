<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan\Rule;

use Brnshkr\Config\PhpStan\Rule\InternalUsageRule;
use Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Internal\InternalClass;
use DaveLiddament\PhpstanRuleTestHelper\AbstractRuleTestCase;
use DaveLiddament\PhpstanRuleTestHelper\Internal\InvalidFixtureFile;
use InvalidArgumentException;
use Override;
use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 *
 * @extends AbstractRuleTestCase<InternalUsageRule>
 */
#[CoversNothing]
final class InternalUsageRuleTest extends AbstractRuleTestCase
{
    private const string FIXTURE_DIRECTORY = __DIR__ . '/../../Fixtures/PhpStan/Rule';

    /**
     * @var ?list<non-empty-string>
     */
    private ?array $allowedInternalTargets = null;

    /**
     * @var ?list<non-empty-string>
     */
    private ?array $allowedDeclaringNamespaces = null;

    /**
     * @var ?list<non-empty-string>
     */
    private ?array $allowedCallingNamespaces = null;

    /**
     * @var ?list<non-empty-string>
     */
    private ?array $allowedSymbols = null;

    /**
     * @throws InvalidFixtureFile
     */
    public function testRule(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/Internal/ScopedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeScopedInternalClass.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleReportsFreeFunctions(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalFunctions.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalFunction.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleReportsFromGlobalNamespace(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/Internal/ScopedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassFromGlobalNamespace.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeScopedInternalClassFromGlobalNamespace.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleComparesNamespacesBySubtree(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassFromNestedNamespace.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassFromSiblingNamespace.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleAllowsTheWholeOrganizationForAVendorTarget(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/OrganizationInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeOrganizationInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeOrganizationInternalClassFromLookalikeNamespace.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleTreatsADescribedTagAsABareInternal(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/DescribedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeDescribedInternalClass.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleIgnoresALeadingBackslashInTheTarget(): void
    {
        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/BackslashTargetInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeBackslashTargetInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeBackslashTargetInternalClassFromOutside.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleExemptsAllowedCallingNamespaces(): void
    {
        $this->allowedCallingNamespaces = ['External\AllowedConsumer'];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleIgnoresALeadingBackslashInAnAllowListEntry(): void
    {
        $this->allowedCallingNamespaces = ['\External\AllowedConsumer'];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleExemptsAllowedDeclaringNamespaces(): void
    {
        $this->allowedDeclaringNamespaces = ['#^Brnshkr\\\Config\\\Tests\\\Fixtures#'];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleExemptsAllowedInternalTargets(): void
    {
        $this->allowedInternalTargets = ['/^Brnshkr\\\Config$/'];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/ScopedInternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeScopedInternalClassAllowed.php',
        );
    }

    /**
     * @throws InvalidFixtureFile
     */
    public function testRuleExemptsAllowedSymbolsIncludingTheirMembers(): void
    {
        $this->allowedSymbols = [InternalClass::class . '::doSomething', InternalClass::class];

        $this->assertIssuesReported(
            self::FIXTURE_DIRECTORY . '/Internal/InternalClass.php',
            self::FIXTURE_DIRECTORY . '/InternalUsage/ConsumeInternalClassAllowed.php',
        );
    }

    /**
     * @throws MissingServiceException
     */
    public function testRuleRejectsAnEmptyOptionEntry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for option "allowedSymbols" must be a list of non-empty strings.');

        // @phpstan-ignore argument.type (Deliberately invalid input to cover the option validation)
        $this->createRule(null, null, null, ['']);
    }

    /**
     * @throws MissingServiceException
     */
    public function testRuleRejectsAnUnterminatedRegexPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entry "/^Brnshkr" for option "allowedCallingNamespaces" is neither a namespace prefix nor a delimited regex pattern.');

        $this->createRule(null, null, ['/^Brnshkr']);
    }

    /**
     * @throws MissingServiceException
     */
    public function testRuleRejectsABackslashOnlyEntry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entry "\" for option "allowedDeclaringNamespaces" is neither a namespace prefix nor a delimited regex pattern.');

        $this->createRule(null, ['\\']);
    }

    /**
     * @throws MissingServiceException
     */
    #[Override]
    protected function getRule(): Rule
    {
        return $this->createRule(
            $this->allowedInternalTargets,
            $this->allowedDeclaringNamespaces,
            $this->allowedCallingNamespaces,
            $this->allowedSymbols,
        );
    }

    /**
     * @param ?list<non-empty-string> $allowedInternalTargets
     * @param ?list<non-empty-string> $allowedDeclaringNamespaces
     * @param ?list<non-empty-string> $allowedCallingNamespaces
     * @param ?list<non-empty-string> $allowedSymbols
     *
     * @throws MissingServiceException
     */
    private function createRule(
        ?array $allowedInternalTargets = null,
        ?array $allowedDeclaringNamespaces = null,
        ?array $allowedCallingNamespaces = null,
        ?array $allowedSymbols = null,
    ): InternalUsageRule {
        return new InternalUsageRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            $allowedInternalTargets,
            $allowedDeclaringNamespaces,
            $allowedCallingNamespaces,
            $allowedSymbols,
        );
    }
}
