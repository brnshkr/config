<?php

namespace Acme\Email;

/**
 * ```php
 * final class Email
 * {
 *     public function send(): void {}
 *
 *     public function getStatus(bool $isSent): string
 *     {
 *         if ($isSent) {
 *             return 'sent';
 *         } else {
 *             return 'queued';
 *         }
 *     }
 * }
 * ```
 */
final class Mailer
{
    public function send(): void {}

    public function getStatus(bool $isSent): string
    {
        if ($isSent) {
            return 'sent';
        } else {
            return 'queued';
        }
    }
}
-----
<?php

namespace Acme\Email;

/**
 * ```php
 * final class Email
 * {
 *     public function send(): void {}
 *
 *     public function getStatus(bool $isSent): string
 *     {
 *         if ($isSent) {
 *             return 'sent';
 *         } else {
 *             return 'queued';
 *         }
 *     }
 * }
 * ```
 */
final class Mailer
{
    public function getStatus(bool $isSent): string
    {
        if ($isSent) {
            return 'sent';
        }
        return 'queued';
    }
}
