<?php

declare(strict_types=1);

namespace Brnshkr\Config;

use JsonException;

use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * @internal
 */
final readonly class Json
{
    private function __construct() {}

    /**
     * @param int<1, max> $depth
     *
     * @throws JsonException
     */
    public static function encode(mixed $data, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, int $depth = 512): string
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/serializer here to keep package as lighweight as possible)
        return json_encode($data, $flags | JSON_THROW_ON_ERROR, $depth) ?: '';
    }

    /**
     * @template TAssociative of bool
     *
     * @param int<1, max> $depth
     * @param TAssociative $isAssociative
     *
     * @return (TAssociative is true ? array<string, mixed> : mixed)
     *
     * @throws JsonException
     */
    public static function decode(string $json, int $flags = 0, int $depth = 512, bool $isAssociative = true): mixed
    {
        // @phpstan-ignore symplify.forbiddenFuncCall (Avoid using symfony/serializer here to keep package as lighweight as possible)
        return json_decode($json, $isAssociative, $depth, $flags | JSON_THROW_ON_ERROR);
    }
}
