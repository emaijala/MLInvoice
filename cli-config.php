<?php
/**
 * CLI configuration for Doctrine console tools.
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

$entityManager = require __DIR__ . '/src/MLInvoice/doctrine.php';

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);
