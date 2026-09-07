<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Exception;

use Brnshkr\Config\Exception\UnreachableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 */
#[CoversClass(UnreachableException::class)]
final class UnreachableExceptionTest extends TestCase
{
    public function testWrappingCarriesTheMessageCodeAndCause(): void
    {
        $runtimeException     = new RuntimeException('cannot read the manifest', 7);
        $unreachableException = UnreachableException::wrap($runtimeException);

        self::assertSame('cannot read the manifest', $unreachableException->getMessage());
        self::assertSame(7, $unreachableException->getCode());
        self::assertSame($runtimeException, $unreachableException->getPrevious());
    }

    public function testWrappingItsOwnTypeReturnsTheSameInstance(): void
    {
        $unreachableException = UnreachableException::wrap(new RuntimeException('once'));

        self::assertSame($unreachableException, UnreachableException::wrap($unreachableException));
    }
}
