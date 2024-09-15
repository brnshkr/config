<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\PhpCsFixerConfig;
use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

use function array_map;
use function getcwd;
use function is_iterable;
use function is_object;
use function iterator_to_array;
use function preg_quote;
use function sprintf;
use function Symfony\Component\String\s;

/**
 * @internal
 */
#[CoversNothing]
final class PhpCsFixerTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @throws DirectoryNotFoundException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws RuntimeException
     */
    public function testExpectedPhpCsFixerConfig(): void
    {
        $config          = PhpCsFixerConfig::get();
        $reflectionClass = new ReflectionClass($config);
        $configArray     = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $value = $reflectionProperty->getValue($config);

            if (is_iterable($value)) {
                $value = array_map(
                    static fn ($item) => is_object($item) ? $item::class : $item,
                    iterator_to_array($value),
                );
            }

            $configArray[$reflectionProperty->getName()] = $value;
        }

        $currentWorkingDirectory = getcwd() ?: '.';
        $result                  = s(Json::encode($configArray))->replaceMatches(sprintf('/%s/', preg_quote($currentWorkingDirectory, '/')), '.')->toString();

        $this->assertMatchesJsonSnapshot($result);
    }
}
