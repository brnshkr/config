<?php

declare(strict_types=1);

namespace Brnshkr\Config\Tests\Fixtures\PhpStan\Rule\Architecture\Message;

/**
 * @internal
 *
 * Passes — an interface is neither final nor readonly
 */
interface UserMessageInterface {}

/**
 * @internal
 *
 * Passes — an abstract base cannot be final
 */
abstract readonly class AbstractUserMessage {}

/**
 * @internal
 *
 * Passes — an enum is neither final nor readonly
 */
enum EmailPriority
{
    case High;
    case Low;
}

/**
 * @internal
 *
 * Passes — final and readonly
 */
final readonly class SendEmailMessage extends AbstractUserMessage {}

/**
 * @internal
 *
 * Fails — a concrete message must be final and readonly
 */
class WelcomeUserMessage {}
