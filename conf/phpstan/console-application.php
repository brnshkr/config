<?php

// @phpstan-ignore symplify.multipleClassLikeInFile (One file is the whole contract with PHPStan: it hands back a console application, and the stubs it needs to be analyzable cannot live anywhere else)
declare(strict_types=1);

/**
 * Hands PHPStan a booted console application so its Symfony extension can read the container.
 *
 * The stubs below stand in for classes a project without Symfony does not have. They exist so this file
 * can be analyzed at all, never to run: the global block returns before them, so nothing here is reachable
 * in a project that actually uses this loader.
 *
 * @internal Brnshkr\Config
 */

namespace {
    use Brnshkr\Config\PhpStan\ProjectKernel;
    use Symfony\Bundle\FrameworkBundle\Console\Application;
    use Symfony\Component\Dotenv\Dotenv;

    require_once ProjectKernel::getRootDirectory() . '/vendor/autoload.php';

    new Dotenv()->bootEnv(ProjectKernel::getRootDirectory() . '/.env');

    // @phpstan-ignore deadCode.unreachable (the stub above only exists where symfony/dotenv is absent, and this loader cannot run there)
    return new Application(new (ProjectKernel::getClassName())(
        ProjectKernel::getEnvironment(),
        (bool) ($_SERVER['APP_DEBUG'] ?? false),
    ));
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

namespace Symfony\Bundle\FrameworkBundle\Console {
    use Brnshkr\Config\Exception\UnreachableException;

    // @phpstan-ignore symplify.forbiddenFuncCall (A stub may only be declared when the real class is absent, which is the one thing this can ask)
    if (!\class_exists(Application::class)) {
        /**
         * @no-named-arguments
         */
        final readonly class Application
        {
            public function __construct(object $kernel)
            {
                throw new UnreachableException(\sprintf(
                    'Stubbed for analysis: %s was booted without symfony/framework-bundle installed, which this loader cannot happen without.',
                    $kernel::class,
                ));
            }
        }
    }
}
