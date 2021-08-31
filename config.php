<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

// Base
define("WEBSITE_URL", "BASE_SITE_URL");

// Dev (SET ALL TO FALSE FOR PRODUCTION)
define("SANDBOX_MODE", false);
define("DISABLE_OUT_ETE", false);
define("DISABLE_INC_ETE", false);
define("DISABLE_METHOD_FORCE", false);
define("DISABLE_UA_LOCK", false);
define("DISABLE_RATE_LIMIT", false);

// Database
define("DB_HOST", "DB_HOST");
define("DB_USER", "DB_USER");
define("DB_PASS", "DB_PASS");
define("DB_SCHEMA", "DB_SCHEMA");
define("DB_PORT", "DB_PORT");

// IPStack API
define("IPSTACK_TOKEN", "IPSTACK_TOKEN");

// JWT
define("JWT_SECRET", "JWT_SECRET");

// AES
define("AES_PW_ENDTOEND", "AES_PW_ENDTOEND" . date('i', time()) . date('H', time()));
define("AES_IV_ENDTOEND", "AES_IV_ENDTOEND");

define("AES_PW_CHECKSUM", 'AES_PW_CHECKSUM' . date('i', time()) . date('H', time()));
define("AES_IV_CHECKSUM", 'AES_IV_CHECKSUM');

define("AES_PW_LAUNCH", 'AES_PW_LAUNCH' . date('i', time()) . date('H', time()));
define("AES_IV_LAUNCH", 'AES_IV_LAUNCH');

// Valid API endpoints
$valid_paths = [
    'auth',
    'integrity',
    'permission',
    'status',
    'log',
    'download',
    'update',
    'config',
];

// Download Branches
$dl_branches = [
    // Group ID => value
    'default' => [4, 7, 9, 10],
    'test' => [4, 9, 10],
    'staff' => [4, 9]
];

// Custom useragent filtering
$allowed_useragents = [
    "agent1",
    "agent2",
];

