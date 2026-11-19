<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * @internal
 */
final class Mailer
{
    public function __construct(
        private string $dsn = '',
        private int $retries = 0,
    ) {}

    public function setUp(string $mode): void {}

    public static function fromDsn(string $dsn): self
    {
        return new self();
    }
}

/**
 * @internal
 */
final class MailerFactory
{
    public static function create(string $region): Mailer
    {
        return new Mailer();
    }
}

/**
 * @internal
 */
final class Unrelated
{
    public function set(string $id, ?string $class = null): self
    {
        return $this;
    }

    public function arg(string $name, mixed $value): self
    {
        return $this;
    }
}

/**
 * @internal
 */
final class Definitions
{
    public function define(ContainerConfigurator $containerConfigurator, Unrelated $unrelated): void
    {
        $services = $containerConfigurator->services();

        $services->set('app.mailer', Mailer::class)
            ->arg('$dsn', 'smtp://localhost')
            ->arg('$retries', 3)
        ;

        $services->set(Mailer::class)
            ->arg('$dsn', 'smtp://localhost')
        ;

        $services->set('app.broken', Mailer::class)
            ->arg('$dns', 'smtp://localhost') // ERROR dns|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\Mailer::__construct()
            ->tag('messenger.transport')
        ;

        $services->set('app.renamed', Mailer::class)
            ->arg('$timeout', 5) // ERROR timeout|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\Mailer::__construct()
        ;

        $services->set('app.inline', Mailer::class)
            ->arg('$dsn', inline_service(Mailer::class)
                ->arg('$dsn', 'smtp://inline'))
        ;

        $services->set('app.inline_broken', Mailer::class)
            ->arg('$dsn', inline_service(Mailer::class)
                ->arg('$dnsInline', 'smtp://inline')) // ERROR dnsInline|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\Mailer::__construct()
        ;

        $unrelated->set('app.mailer', Mailer::class)
            ->arg('$nothingToDoWithServices', 1)
        ;

        $services->set('app.args', Mailer::class)
            ->args([
                '$dsn' => 'smtp://localhost',
                '$dns' => 'smtp://localhost', // ERROR dns|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\Mailer::__construct()
            ])
        ;

        $services->set('app.bind', Mailer::class)
            ->bind('$dns', 'smtp://localhost') // ERROR dns|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\Mailer::__construct()
        ;

        $services->set('app.call', Mailer::class)
            ->call('setUp', [
                '$mode' => 'strict',
                '$nope' => 'strict', // ERROR nope|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\Mailer::setUp()
            ])
        ;

        $services->set('app.factory', Mailer::class)
            ->factory([MailerFactory::class, 'create'])
            ->arg('$region', 'eu')
            ->arg('$dsn', 'smtp://localhost') // ERROR dsn|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\MailerFactory::create()
        ;

        $services->set('app.factory_last', Mailer::class)
            ->arg('$region', 'eu')
            ->factory([MailerFactory::class, 'create'])
        ;

        $services->set('app.factory_unresolved', Mailer::class)
            ->factory('some_factory_function')
            ->arg('$whatever', 1)
        ;

        $services->defaults()
            ->bind('$dns', 'smtp://localhost')
        ;

        $services->set('app.unknown_method', Mailer::class)
            ->call('missing', ['$dns' => 'smtp://localhost'])
        ;

        $services->set('app.factory_first_class', Mailer::class)
            ->factory(MailerFactory::create(...))
            ->arg('$dsn', 'smtp://localhost') // ERROR dsn|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\MailerFactory::create()
        ;

        $services->set('app.factory_string', Mailer::class)
            ->factory('Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\MailerFactory::create')
            ->arg('$dsn', 'smtp://localhost') // ERROR dsn|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\MailerFactory::create()
        ;

        $services->set('app.constructor', Mailer::class)
            ->constructor('fromDsn')
            ->arg('$nope', 'smtp://localhost') // ERROR nope|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\Mailer::fromDsn()
        ;

        $services->set('app.factory_own_class', Mailer::class)
            ->factory([null, 'fromDsn'])
            ->arg('$nope', 'smtp://localhost') // ERROR nope|Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\ServiceArgumentBinding\Mailer::fromDsn()
        ;

        $services->set('app.factory_service', Mailer::class)
            ->factory(service('app.mailer_factory'))
            ->arg('$whatever', 1)
        ;
    }
}
