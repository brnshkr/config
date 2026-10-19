<?php

namespace Acme\User;

/**
 * ```php
 * if ($isActive === true) {
 *     return true;
 * }
 *
 * return false;
 * ```
 */
final class User {}
-----
<?php

namespace Acme\User;

/**
 * ```php
 * return $isActive === true;
 * ```
 */
final class User {}
