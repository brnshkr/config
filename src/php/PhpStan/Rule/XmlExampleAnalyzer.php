<?php

declare(strict_types=1);

namespace Brnshkr\Config\PhpStan\Rule;

use Brnshkr\Config\Str;
use Brnshkr\Config\Tests\PhpStan\Rule\FencedCodeRuleTest;
use Dom\Element;
use Dom\XMLDocument;
use DOMException;
use LibXMLError;

use function array_find;
use function array_map;
use function array_slice;
use function class_exists;
use function is_readable;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;

use const LIBXML_NONET;

/**
 * @internal
 *
 * @phpstan-import-type ExampleError from FencedCodeRule
 *
 * @see FencedCodeRuleTest
 */
final readonly class XmlExampleAnalyzer
{
    private const string SCHEMA_INSTANCE_NAMESPACE = 'http://www.w3.org/2001/XMLSchema-instance';

    public function __construct(
        private string $projectDirectory,
    ) {}

    /**
     * @return list<ExampleError>
     */
    public function analyze(string $code): array
    {
        if (!class_exists(XMLDocument::class) || Str::isEmpty($code)) {
            return [];
        }

        $isUsingInternalErrors = libxml_use_internal_errors(true);

        try {
            $xmlDocument = XMLDocument::createFromString($code, LIBXML_NONET);
            $schemaPath  = $this->findSchemaPath($xmlDocument);

            return $schemaPath === null || $xmlDocument->schemaValidate($schemaPath)
                ? []
                : self::collectErrors('Code block does not match its schema');
        } catch (DOMException) {
            return array_slice(self::collectErrors(FencedCodeRule::PROBLEM_PARSE), 0, 1);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($isUsingInternalErrors);
        }
    }

    /**
     * @return list<ExampleError>
     */
    private static function collectErrors(string $problem): array
    {
        return array_map(
            static fn (LibXMLError $libXmlError): array => FencedCodeRule::buildExampleError(
                $problem,
                $libXmlError->message,
                $libXmlError->line,
            ),
            libxml_get_errors(),
        );
    }

    /**
     * @return ?non-empty-string
     */
    private function findSchemaPath(XMLDocument $xmlDocument): ?string
    {
        $rootElement = $xmlDocument->documentElement;

        if (!$rootElement instanceof Element) {
            return null;
        }

        $namespacedSchemaLocation = array_find(
            Str::matchAll(
                $rootElement->getAttributeNS(self::SCHEMA_INSTANCE_NAMESPACE, 'schemaLocation') ?? '',
                '/(?<!\S)(?<namespace>\S+)\s+(?<location>\S+)/',
            ),
            static fn (array $locationGroups): bool => ($locationGroups['namespace'] ?? null)
                === $rootElement->namespaceURI,
        );

        $schemaLocation = Str::trim(
            $namespacedSchemaLocation['location']
                ?? $rootElement->getAttributeNS(self::SCHEMA_INSTANCE_NAMESPACE, 'noNamespaceSchemaLocation')
                ?? '',
        );

        if (Str::isEmpty($schemaLocation) || Str::contains($schemaLocation, '://')) {
            return null;
        }

        $schemaPath = $this->projectDirectory . '/' . Str::trimPrefix($schemaLocation, './');

        return is_readable($schemaPath) ? $schemaPath : null;
    }
}
