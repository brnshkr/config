<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests;

use Brnshkr\Config\Json;
use Brnshkr\Config\RectorConfig;
use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;
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
final class RectorTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @throws DirectoryNotFoundException
     * @throws JsonException
     * @throws RuntimeException
     */
    public function testExpectedRectorConfig(): void
    {
        $rectorConfigBuilder = RectorConfig::get();
        $reflectionClass     = new ReflectionClass($rectorConfigBuilder);
        $configArray         = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $value = $reflectionProperty->getValue($rectorConfigBuilder);

            if (is_iterable($value)) {
                $value = array_map(
                    static fn ($item) => is_object($item) ? $item::class : $item,
                    iterator_to_array($value),
                );
            }

            $configArray[$reflectionProperty->getName()] = $value;
        }

        $result = Json::encode($configArray);
        $result = s($result)->replaceMatches(sprintf('/%s/', preg_quote(getcwd() ?: '.', '/')), '.')->toString();

        $this->assertMatchesJsonSnapshot($result);
    }
}
