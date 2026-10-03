<?php

declare(strict_types=1);

namespace Acme\User;

function inspectUser(string $name): int
{
    acme_undefined($name);
    acme_dump($name); // ERROR Function `acme_dump()` comes from development-only package `acme/dumper` and must not be called from production code.

    return mb_strlen($name);
}
