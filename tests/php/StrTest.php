<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_key_first;
use function is_string;
use function sprintf;

use const PHP_INT_MAX;

/**
 * @internal
 */
#[CoversClass(Str::class)]
final class StrTest extends TestCase
{
    #[DataProvider('provideAStringIsANonDecimalIntStringExactlyWhenPhpKeepsItAsAnArrayKeyCases')]
    public function testAStringIsANonDecimalIntStringExactlyWhenPhpKeepsItAsAnArrayKey(string $candidate): void
    {
        $key = array_key_first([$candidate => true]);

        self::assertSame(is_string($key), Str::isNonDecimalIntString($candidate));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAStringIsANonDecimalIntStringExactlyWhenPhpKeepsItAsAnArrayKeyCases(): iterable
    {
        foreach (['/app/composer.json', 'App\\', '', '-0', '01', ' 1', '1.5', PHP_INT_MAX . '0', '0', '1', '-1', (string) PHP_INT_MAX] as $candidate) {
            yield sprintf('"%s"', $candidate) => [$candidate];
        }
    }
}
