<?php

// AES Utility

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class AES
{

    /**
     * Encrypt the provided data
     *
     * @param string $data
     * @param string $key
     * @param string $iv
     * @param string $method
     *
     * @return string
     */
    public static function encrypt($data, $key, $iv, $method = 'AES-256-CFB')
    {
        return openssl_encrypt($data, $method, $key, 0, $iv);
    }

    /**
     * Decrypt the provided data
     *
     * @param string $data
     * @param string $key
     * @param string $iv
     * @param string $method
     *
     * @return string
     */
    public static function decrypt($data, $key, $iv, $method = 'AES-256-CFB')
    {
        return openssl_decrypt($data, $method, $key, 0, $iv);
    }


}