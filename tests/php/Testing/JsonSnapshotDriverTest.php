<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Testing;

use Brnshkr\Config\Json;
use Brnshkr\Config\Testing\JsonSnapshotDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(JsonSnapshotDriver::class)]
#[UsesClass(Json::class)]
final class JsonSnapshotDriverTest extends TestCase
{
    public function testMatchesWhatDecodesToTheSameValue(): void
    {
        new JsonSnapshotDriver()->match('{"paths": ["./src"]}', '{"paths":["./src"]}');
    }

    public function testReportsADifferenceAsAFailedAssertion(): void
    {
        $this->expectException(ExpectationFailedException::class);

        new JsonSnapshotDriver()->match('{"paths":["./src"]}', '{"paths":["./tests"]}');
    }
}
