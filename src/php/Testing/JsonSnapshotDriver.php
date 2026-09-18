<?php

declare(strict_types=1);

namespace Brnshkr\Config\Testing;

use Brnshkr\Config\Json;
use Brnshkr\Config\Tests\Testing\JsonSnapshotDriverTest;
use JsonException;
use Override;
use PHPUnit\Framework\Assert;
use Spatie\Snapshots\Drivers\JsonDriver;

use function is_string;

/**
 * Matches a JSON snapshot on the decoded value rather than on the encoded string.
 *
 * The shipped driver compares two single-line encodings, so a failure prints each of them whole.
 * Comparing the decoded values lets the runner align them entry by entry and shorten what it prints,
 * and leaves the order of an object's keys free while the order of a list still counts.
 *
 * @see https://github.com/brnshkr/config/blob/master/docs/php/JsonSnapshotDriver.md
 *
 * @api
 *
 * @no-named-arguments
 *
 * @see JsonSnapshotDriverTest
 */
final class JsonSnapshotDriver extends JsonDriver
{
    /**
     * Compare the snapshot with what the run produced, entry by entry.
     *
     * @example
     * ```php
     * $this->assertMatchesSnapshot($report, new JsonSnapshotDriver());
     * ```
     *
     * @param mixed $expected the snapshot as it was written
     * @param mixed $actual what this run produced
     *
     * @throws JsonException when either side is a string that is not JSON
     */
    #[Override]
    public function match(mixed $expected, mixed $actual): void
    {
        Assert::assertEquals(self::decode($expected), self::decode($actual));
    }

    /**
     * @throws JsonException
     */
    private static function decode(mixed $value): mixed
    {
        return is_string($value) ? Json::decode($value) : $value;
    }
}
