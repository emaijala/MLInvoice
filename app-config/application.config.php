<?php

// Global Configuration; do not modify.
// Add any overrides in application.local.config.php file.
// See application.local.config.php.example for an example.

// Default locale if configuration is missing
defined('MLINVOICE_FALLBACK_LOCALE') || define('MLINVOICE_FALLBACK_LOCALE', 'en-US');

// Running environment. Valid options are 'production' and 'development'.
defined('MLINVOICE_ENV') || define('MLINVOICE_ENV', getenv('MLINVOICE_ENV') ?? 'production');

// JS debug flag
defined('MLINVOICE_JS_DEBUG') || define('MLINVOICE_JS_DEBUG', false);

// Base directory
defined('MLINVOICE_BASE_DIR') || define('MLINVOICE_BASE_DIR', realpath(__DIR__ . '/..'));

// Cache directory
defined('MLINVOICE_CACHE_DIR') || define('MLINVOICE_CACHE_DIR', MLINVOICE_BASE_DIR . '/local/cache');

// Login delay multiplier
defined('MLINVOICE_LOGIN_DELAY_MULTIPLIER') || define('MLINVOICE_LOGIN_DELAY_MULTIPLIER', 1);

// Database datetime format
defined('MLINVOICE_DATABASE_DATETIME_FORMAT') || define('MLINVOICE_DATABASE_DATETIME_FORMAT', 'Ymd');

// Base path for URLs
defined('MLINVOICE_BASE_URL_PATH') || define('MLINVOICE_BASE_URL_PATH', (function () {
  // Determine correct base path:
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
  $uri = (string) parse_url('http://a' . $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
  if (str_starts_with($uri, $_SERVER['SCRIPT_NAME'])) {
      return $_SERVER['SCRIPT_NAME'];
  }
  if ($scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
      return $scriptDir;
  }
  if (str_ends_with($scriptDir, '/public') && strncmp($uri, $scriptDir, strlen($scriptDir) - 7) === 0) {
      return substr($scriptDir, 0, -7);
  }
  return '';
})());

// Role constants
define('MLINVOICE_USER_ROLE_READONLY', 0);
define('MLINVOICE_USER_ROLE_USER', 1);
define('MLINVOICE_USER_ROLE_BACKUPMGR', 90);
define('MLINVOICE_USER_ROLE_ADMIN', 99);
