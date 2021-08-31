<?php

// Cryptography Utility

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

interface Helper
{
    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return self
     */
    public static function i();

}
