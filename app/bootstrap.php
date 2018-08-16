<?php declare(strict_types = 1);

use Nette\Configurator;

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Configurator();

$configurator->setDebugMode(true);
$configurator->enableDebugger(__DIR__ . '/../var/log');
$configurator->setTempDirectory(__DIR__ . '/../var/temp');

$configurator->addConfig(__DIR__ . '/config.neon');
$configurator->addConfig(__DIR__ . '/config.local.neon');

return $configurator->createContainer();
