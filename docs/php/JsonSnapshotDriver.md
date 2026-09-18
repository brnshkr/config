# JsonSnapshotDriver [🔍](../../src/php/Testing/JsonSnapshotDriver.php 'Go to source')

`Brnshkr\Config\Testing\JsonSnapshotDriver` is the snapshot driver a JSON snapshot test passes to
[spatie/phpunit-snapshot-assertions](https://github.com/spatie/phpunit-snapshot-assertions),
so a mismatch prints the entries that differ rather than both encodings whole.

## Usage

```php
use Brnshkr\Config\Testing\JsonSnapshotDriver;
use Spatie\Snapshots\MatchesSnapshots;

final class ReportTest extends TestCase
{
    use MatchesSnapshots;

    public function testPrintsExpectedReport(): void
    {
        $this->assertMatchesSnapshot($report, new JsonSnapshotDriver());
    }
}
```

The snapshot file keeps the `.json` extension and the shipped formatting,
so an existing snapshot needs no regeneration.

## Customizing

Nothing to configure. The order of an object's keys is free and the order of a list counts;
set `shortenArraysForExportThreshold` in the PHPUnit configuration to cap how much of a long list a failure prints.
