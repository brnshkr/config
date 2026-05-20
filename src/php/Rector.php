<?php

/**
 * @api
 */

declare(strict_types=1);

namespace Brnshkr\Config;

use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\String_\SimplifyQuoteEscapeRector;
use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php82\Rector\Param\AddSensitiveParameterAttributeRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use RuntimeException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

use function array_map;

Module::warnMissingPackages(Module::MODULE_RECTOR);

/**
 * Builds a ready-to-use Rector config that captures the @brnshkr refactoring decisions.
 *
 * The `#[\SensitiveParameter]` attribute rule is pre-wired to a list of parameter names commonly
 * associated with secrets (e.g. `password`, `apiToken`, `clientSecret`, plus plural variants), so
 * newly introduced sensitive parameters automatically get the attribute added.
 *
 * @no-named-arguments
 */
final readonly class Rector
{
    /**
     * @phpstan-var non-empty-list<non-empty-string>
     */
    private const array SENSITIVE_PARAMETERS = [
        'accessKey',
        'accessKeyId',
        'accessToken',
        'accountKey',
        'accountSecret',
        'amqpDsn',
        'apiKey',
        'apiKeyId',
        'apiSecret',
        'apiSecretKey',
        'apiToken',
        'appSecret',
        'authenticationToken',
        'authHeader',
        'authorization',
        'authorizationHeader',
        'authorizationToken',
        'authToken',
        'basicAuthPassword',
        'basicAuthUsername',
        'bcc',
        'bccAddress',
        'bccEmailAddress',
        'bearerToken',
        'cc',
        'ccAddress',
        'ccEmailAddress',
        'certificate',
        'certificateKey',
        'clientAssertion',
        'clientId',
        'clientSecret',
        'connectionString',
        'consumerKey',
        'consumerSecret',
        'credentials',
        'csrfToken',
        'databasePassword',
        'dbPassword',
        'decryptionKey',
        'doctrineDsn',
        'dsn',
        'email',
        'emailAddress',
        'encryptionKey',
        'fromAddress',
        'fromEmailAddress',
        'idToken',
        'jwt',
        'jwtToken',
        'keyStorePassword',
        'lockDsn',
        'mailerDsn',
        'oAuthToken',
        'passphrase',
        'password',
        'privateKey',
        'redisDsn',
        'refreshToken',
        'replyAddress',
        'replyToAddress',
        'secret',
        'secretAccessKey',
        'secretKey',
        'secretToken',
        'serviceAccountJson',
        'serviceAccountKey',
        'sessionId',
        'sessionToken',
        'signature',
        'signatureKey',
        'signedUrl',
        'signingKey',
        'sshKey',
        'sshPrivateKey',
        'tlsCertificate',
        'tlsKey',
        'tlsPrivateKey',
        'toAddress',
        'toEmailAddress',
        'token',
        'tokenId',
        'trustStorePassword',
        'username',
        'verificationKey',
        'webhookSecret',
    ];

    private function __construct() {}

    /**
     * Build a fully configured RectorConfigBuilder.
     *
     * Caller may pass a Finder to narrow paths under analysis; otherwise the project-wide
     * {@see FileFinder} defaults apply. The returned builder can be further customised
     * before being returned from `conf/rector.php`.
     *
     * @example
     * ```php
     * // conf/rector.php
     * return Rector::getConfig()->withSkip([SomeOtherRule::class]);
     * ```
     *
     * @param ?Finder $finder Pre-configured Finder to extend, or null for project defaults
     *
     * @return RectorConfigBuilder Configured builder ready for further customization or return
     *
     * @throws DirectoryNotFoundException When FileFinder cannot resolve the source directory
     * @throws RuntimeException When required Rector dependencies are missing
     */
    public static function getConfig(?Finder $finder = null): RectorConfigBuilder
    {
        /** @disregard P1009 Some skipped classes come bundled with rector and are not picked up by intelephense */
        $rectorConfigBuilder = RectorConfig::configure()
            ->withCache('.cache/rector.cache')
            ->withRootFiles()
            ->withPaths(FileFinder::getFilePaths($finder))
            ->withPhpSets()
            ->withAttributesSets()
            ->withPreparedSets(
                deadCode: true,
                codeQuality: true,
                codingStyle: true,
                typeDeclarations: true,
                privatization: true,
                naming: true,
                instanceOf: true,
                earlyReturn: true,
                rectorPreset: true,
                phpunitCodeQuality: true,
                doctrineCodeQuality: true,
                symfonyCodeQuality: true,
                symfonyConfigs: true,
            )
            ->withImportNames(
                importNames: false,
                importDocBlockNames: false,
                importShortClasses: false,
                removeUnusedImports: true,
            )
            ->withSkip([
                LocallyCalledStaticMethodToNonStaticRector::class,
                NewlineBeforeNewAssignSetRector::class,
                NewlineBetweenClassLikeStmtsRector::class,
                NullToStrictStringFuncCallArgRector::class,
                PreferPHPUnitThisCallRector::class,
                SimplifyQuoteEscapeRector::class,
            ])
            ->withConfiguredRule(AddOverrideAttributeToOverriddenMethodsRector::class, [
                AddOverrideAttributeToOverriddenMethodsRector::ADD_TO_INTERFACE_METHODS    => true,
                AddOverrideAttributeToOverriddenMethodsRector::ALLOW_OVERRIDE_EMPTY_METHOD => true,
            ])
            ->withConfiguredRule(AddSensitiveParameterAttributeRector::class, [
                AddSensitiveParameterAttributeRector::SENSITIVE_PARAMETERS => [
                    ...self::SENSITIVE_PARAMETERS,
                    ...array_map(
                        static fn (string $parameter): string => Str::doesEndWith($parameter, 's')
                            ? ($parameter . 'es')
                            : ($parameter . 's'),
                        self::SENSITIVE_PARAMETERS,
                    ),
                ],
            ])
            ->withConfiguredRule(EncapsedStringsToSprintfRector::class, [
                EncapsedStringsToSprintfRector::ALWAYS => true,
            ])
        ;

        $editorUrl = EditorUrl::forRector();

        if ($editorUrl !== null) {
            $rectorConfigBuilder->withEditorUrl($editorUrl);
        }

        return $rectorConfigBuilder;
    }
}

return Rector::getConfig();
