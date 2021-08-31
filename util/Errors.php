<?php

// Cryptography Utility

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Errors
{

    /**
     * @breif User/Token could not be authenticated
     */
    public static function Unauthorized(): void
    {
        API::i("UNAUTHORIZED", 403)->output();
    }

    /**
     * @breif Provided HWID did not match with the stored HWID
     */
    public static function HwidBan(): void
    {
        API::i("INTERNAL_ERROR", 200)->output();
    }

    /**
     * @breif Internal server error (Db failed exec, syntax mistake, etc.)
     */
    public static function Internal(): void
    {
        API::i("INTERNAL_ERROR", 500)->output();
    }

    /**
     * @breif Provided parameters are invalid (Not provided / Not acceptable)
     */
    public static function Params(): void
    {
        API::i("INVALID_PARAMS", 412)->output();
    }

    /**
     * @breif User is already banned
     */
    public static function Banned(): void
    {
        API::i("BANNED", 403)->output();
    }

    /**
     * @breif Provided JWT token is invalid
     */
    public static function Expired(): void
    {
        API::i("INVALID_TOKEN", 400)->output();
    }

    /**
     * @breif Request type is not supported
     */
    public static function Request(): void
    {
        API::i("BAD_METHOD", 405)->output();
    }

    /**
     * @breif Requested item is not found
     */
    public static function NotFound(): void
    {
        API::i("NOT_FOUND", 404)->output();
    }

    /**
     * @breif Rate limit the user
     */
    public static function RateLimit(): void
    {
        API::i("RATE_LIMITED", 429)->output();
    }
}