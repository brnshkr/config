<?php

// @phpstan-ignore symplify.multipleClassLikeInFile (One file is the whole contract with PHPStan: it hands back an object manager, and the stubs it needs to be analyzable cannot live anywhere else)
declare(strict_types=1);

/**
 * Hands PHPStan a booted object manager so its Doctrine extension can read the entity mappings.
 *
 * The stubs below stand in for classes a project without Symfony or Doctrine does not have. They exist so
 * this file can be analyzed at all, never to run: the global block returns before them, so nothing here is
 * reachable in a project that actually uses this loader.
 *
 * @internal Brnshkr\Config
 */

namespace {
    use App\Kernel;
    use Brnshkr\Config\PhpStan\ProjectKernel;
    use Doctrine\Persistence\ManagerRegistry;
    use Symfony\Component\Dotenv\Dotenv;

    require_once ProjectKernel::getRootDirectory() . '/vendor/autoload.php';

    new Dotenv()->bootEnv(ProjectKernel::getRootDirectory() . '/.env');

    /**
     * @var Kernel $kernel
     */
    // @phpstan-ignore deadCode.unreachable (the stub above only exists where symfony/dotenv is absent, and this loader cannot run there)
    $kernel = new (ProjectKernel::getClassName())(
        ProjectKernel::getEnvironment(),
        (bool) ($_SERVER['APP_DEBUG'] ?? false),
    );

    $kernel->boot();

    /**
     * @var ManagerRegistry $doctrine
     */
    $doctrine = $kernel->getContainer()->get('doctrine');

    return $doctrine->getManager();
}

namespace App {
    use Brnshkr\Config\Exception\UnreachableException;
    use Symfony\Component\DependencyInjection\ContainerInterface;

    // @phpstan-ignore symplify.forbiddenFuncCall (A stub may only be declared when the real class is absent, which is the one thing this can ask)
    if (!\class_exists(Kernel::class)) {
        /**
         * @no-named-arguments
         */
        final readonly class Kernel
        {
            public function __construct(string $environment, bool $isDebug)
            {
                throw new UnreachableException(\sprintf(
                    'Stubbed for analysis: no application kernel exists to boot in "%s"%s.',
                    $environment,
                    $isDebug ? ' with debug on' : '',
                ));
            }

            public function boot(): never
            {
                throw new UnreachableException('Stubbed for analysis: the constructor already refused to build a kernel.');
            }

            public function getContainer(): ContainerInterface
            {
                throw new UnreachableException('Stubbed for analysis: the constructor already refused to build a kernel.');
            }
        }
    }
}

namespace Symfony\Component\Dotenv {
    use Brnshkr\Config\Exception\UnreachableException;

    // @phpstan-ignore symplify.forbiddenFuncCall (A stub may only be declared when the real class is absent, which is the one thing this can ask)
    if (!\class_exists(Dotenv::class)) {
        /**
         * @no-named-arguments
         */
        final class Dotenv
        {
            public function bootEnv(string $path): never
            {
                throw new UnreachableException(\sprintf(
                    'Stubbed for analysis: "%s" is only read where symfony/dotenv is installed.',
                    $path,
                ));
            }
        }
    }
}

namespace Doctrine\Persistence {
    // @phpstan-ignore symplify.forbiddenFuncCall (A stub may only be declared when the real interface is absent, which is the one thing this can ask)
    if (!\interface_exists(ManagerRegistry::class)) {
        /**
         * @no-named-arguments
         *
         * @phpstan-ignore symplify.explicitInterfaceSuffixName (The name has to match Doctrine's own interface exactly or the stub stands in for nothing)
         */
        interface ManagerRegistry
        {
            public function getManager(): object;
        }
    }
}
