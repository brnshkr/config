<?php

declare(strict_types=1);

/**
 * @internal
 */
return [
    'matesofmate/composer-extension' => [
        'enabled' => true,
        'skills'  => [
            'composer-dependency-changes' => [
                'enabled'     => true,
                'mode'        => 'managed',
                'state'       => 'managed',
                'source'      => 'vendor/matesofmate/composer-extension/skills/composer-dependency-changes',
                'source_hash' => 'sha256:a606582cc5c83bea9ed812e4ccd3f1f1d34cc275efbbd45130844b60a3f15662',
                'hash'        => 'sha256:d5fbd0ff8a3905ad7bdf96ddb0ddc7d2eb18c6759b9d64ed90004da268094fa8',
                'targets'     => [
                    '.agents/skills/mate-composer-dependency-changes',
                    '.claude/skills/mate-composer-dependency-changes',
                ],
            ],
            'composer-dependency-conflicts' => [
                'enabled'     => true,
                'mode'        => 'managed',
                'state'       => 'managed',
                'source'      => 'vendor/matesofmate/composer-extension/skills/composer-dependency-conflicts',
                'source_hash' => 'sha256:5c04957aa53caf766a4f26ec70aae5f8bebd771cad9ef0439231a0f53599f368',
                'hash'        => 'sha256:c192c289d2a9cc739d5bb849fd1c83d1f9c4adb5aa7f2fff76fcb0f7b690154d',
                'targets'     => [
                    '.agents/skills/mate-composer-dependency-conflicts',
                    '.claude/skills/mate-composer-dependency-conflicts',
                ],
            ],
        ],
    ],
    'matesofmate/phpstan-extension' => [
        'enabled' => true,
        'skills'  => [
            'phpstan-static-analysis' => [
                'enabled'     => true,
                'mode'        => 'managed',
                'state'       => 'managed',
                'source'      => 'vendor/matesofmate/phpstan-extension/skills/phpstan-static-analysis',
                'source_hash' => 'sha256:74b0a0aa44f5a2d742ac3afa6404135243735c60101b3e90d9b4d5c136539d0d',
                'hash'        => 'sha256:f4cd7855bf57839ac489698c8d44e2636ad5ecb3296c3b5ecd2d3b3637b27b4e',
                'targets'     => [
                    '.agents/skills/mate-phpstan-static-analysis',
                    '.claude/skills/mate-phpstan-static-analysis',
                ],
            ],
        ],
    ],
    'matesofmate/phpunit-extension' => [
        'enabled' => true,
        'skills'  => [
            'phpunit-test-run' => [
                'enabled'     => true,
                'mode'        => 'managed',
                'state'       => 'managed',
                'source'      => 'vendor/matesofmate/phpunit-extension/skills/phpunit-test-run',
                'source_hash' => 'sha256:2d9660b78bbe1bba10ed5440652db7358a5c5d2bfeecabea64e96cedb51d3855',
                'hash'        => 'sha256:02784906ebc956d1f650d59275b8ee60ebe59f37d0265fa3aeda850d143e1171',
                'targets'     => [
                    '.agents/skills/mate-phpunit-test-run',
                    '.claude/skills/mate-phpunit-test-run',
                ],
            ],
        ],
    ],
    'matesofmate/rector-extension' => [
        'enabled' => true,
        'skills'  => [
            'rector-refactoring' => [
                'enabled'     => true,
                'mode'        => 'managed',
                'state'       => 'managed',
                'source'      => 'vendor/matesofmate/rector-extension/skills/rector-refactoring',
                'source_hash' => 'sha256:bab5873472d28c2db9cf21a4859fcdeccf64a976e78de87727ee969811349859',
                'hash'        => 'sha256:adf79054c1b988179714277697ad2a51d422e64969eb4a5e4cd98bdb15eda0eb',
                'targets'     => [
                    '.agents/skills/mate-rector-refactoring',
                    '.claude/skills/mate-rector-refactoring',
                ],
            ],
        ],
    ],
    'symfony/ai-mate' => [
        'enabled' => true,
        'skills'  => [
            'php-environment-check' => [
                'enabled'     => true,
                'mode'        => 'managed',
                'state'       => 'managed',
                'source'      => 'vendor/symfony/ai-mate/skills/php-environment-check',
                'source_hash' => 'sha256:475400c87571d228335fb80f414c56a80110e182268df65f9f84bc9bfb2aa6f3',
                'hash'        => 'sha256:56de92962de6233284c439de64b1cef66487c56c07e6c3357658139cf4d82527',
                'targets'     => [
                    '.agents/skills/mate-php-environment-check',
                    '.claude/skills/mate-php-environment-check',
                ],
            ],
            'system-information' => [
                'enabled'     => true,
                'mode'        => 'managed',
                'state'       => 'managed',
                'source'      => 'vendor/symfony/ai-mate/skills/system-information',
                'source_hash' => 'sha256:7249544b603ecd416fae22cd92b465257619306f88a50dc8efd9653606e9e460',
                'hash'        => 'sha256:fcbfe6ea831b35299f5ba646bf63dbd761e184e7ad196087a350a060f9915973',
                'targets'     => [
                    '.agents/skills/mate-system-information',
                    '.claude/skills/mate-system-information',
                ],
            ],
        ],
    ],
];
