<?php

declare(strict_types=1);

namespace Acme\User;

final class ReadRequest
{
    public function getName(string $key): string
    {
        $server = ['USER_NAME' => 'acme'];
        $name   = $_SERVER['USER_NAME'] ?? $server['USER_NAME']; // ERROR Superglobal `$_SERVER` must not be used. Read the environment in one place and inject the value instead.

        $_ENV['USER_NAME'] = $name; // ERROR Superglobal `$_ENV` must not be used. Read the environment in one place and inject the value instead.

        if (isset($_SESSION[$key])) { // ERROR Superglobal `$_SESSION` must not be used. Use the session instead.
            unset($_SESSION[$key]); // ERROR Superglobal `$_SESSION` must not be used. Use the session instead.
        }

        return (string) ($GLOBALS[$key] ?? $_GET[$key] ?? $$key); // ERROR Superglobal `$GLOBALS` must not be used. Pass the value explicitly instead.
    }
}
