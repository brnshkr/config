#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Brnshkr\Config\Composer;

use Brnshkr\Config\Composer\Command\CommandProvider;
use Composer\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$application = new Application();

$application->addCommands(new CommandProvider()->getCommands());
$application->run();
