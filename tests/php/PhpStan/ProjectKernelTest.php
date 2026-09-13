<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\PhpStan;

use App\Kernel;
use Brnshkr\Config\PhpStan\ProjectKernel;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function putenv;
use function sprintf;

/**
 * @internal
 */
#[CoversClass(ProjectKernel::class)]
final class ProjectKernelTest extends TestCase
{
    public function testTheKernelDefaultsToTheConventionalClass(): void
    {
        self::assertSame(Kernel::class, ProjectKernel::getClassName());
    }

    public function testTheEnvironmentVariableNamesTheKernel(): void
    {
        putenv(sprintf('%s=Acme\Kernel', ProjectKernel::CLASS_ENVIRONMENT_VARIABLE));

        self::assertSame('Acme\Kernel', ProjectKernel::getClassName());
    }

    public function testAProjectWithoutTheConventionalKernelLocatesNothing(): void
    {
        self::assertNull(ProjectKernel::locate());
    }

    public function testAClassNamedByTheEnvironmentIsResolvedThroughThePsr4Map(): void
    {
        putenv(sprintf('%s=%s', ProjectKernel::CLASS_ENVIRONMENT_VARIABLE, ProjectKernel::class));

        self::assertStringEndsWith('src/php/PhpStan/ProjectKernel.php', (string) ProjectKernel::locate());
    }

    public function testAnExplicitlyNamedKernelThatResolvesToNoFileFails(): void
    {
        putenv(sprintf('%s=Acme\Nope', ProjectKernel::CLASS_ENVIRONMENT_VARIABLE));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains(ProjectKernel::CLASS_ENVIRONMENT_VARIABLE);
        ProjectKernel::locate();
    }

    public function testAProjectWithoutACompiledContainerLocatesNothing(): void
    {
        self::assertNull(ProjectKernel::locateContainerXml());
    }

    public function testTheShippedLoadersExist(): void
    {
        self::assertFileExists(ProjectKernel::getLoaderPath('console-application'));
        self::assertFileExists(ProjectKernel::getLoaderPath('object-manager'));
    }

    #[After]
    public function clearKernelEnvironmentVariable(): void
    {
        putenv(ProjectKernel::CLASS_ENVIRONMENT_VARIABLE);
    }
}
