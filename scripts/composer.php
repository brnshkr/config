#!/usr/bin/env php
<?php

/**
 * @internal Brnshkr\Config\Composer
 */

declare(strict_types=1);

use Brnshkr\Config\Composer\Command\CommandProvider;
use Brnshkr\Config\Composer\Plugin;
use Composer\Composer;
use Composer\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

require __DIR__ . '/../vendor/autoload.php';

$application     = new Application();
$eventDispatcher = new EventDispatcher();

$eventDispatcher->addListener(ConsoleEvents::COMMAND, static function () use ($application): void {
    $composer = $application->getComposer(false);

    if ($composer instanceof Composer) {
        $composer->getPluginManager()->addPlugin(new Plugin(), false, $composer->getPackage());
    }
});

$application->setDispatcher($eventDispatcher);
$application->addCommands(new CommandProvider()->getCommands());
$application->run();
