<?php

// Cryptography Utility

/* To prevent PHP errors (extending class does not exist) revealing path */

use JWT\BeforeValidException;
use JWT\ExpiredException;
use JWT\JWT;
use JWT\SignatureInvalidException;

if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Crypto
{
    /**
     * Verify that the provided JWT is real
     *
     * @param string $token
     *
     * @return bool
     */
    public static function verify($token): bool
    {
        // Catch every possible exception because Firebase loves them for some odd reason
        try {
            JWT::decode($token, JWT_SECRET, ['HS256']);
        }
        catch (ExpiredException $e) {
            Errors::Expired();
        }
        catch (BeforeValidException | SignatureInvalidException $e) {
            return false;
        }
        catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Decode a JWT token and return the payload
     *
     * @param $string token
     *
     * @return bool|object
     */
    public static function decryptPayload($token)
    {
        // Once again, catch every possible error!
        try {
            $res = JWT::decode($token, JWT_SECRET, ['HS256']);
        }
        catch (ExpiredException $e) {
            Errors::Expired();
        }
        catch (BeforeValidException | SignatureInvalidException $e) {
            return false;
        }
        catch (Exception $e) {
            return false;
        }
        return $res;
    }

    /**
     * Generate a JWT token
     *
     * @param int    $id
     * @param string $username
     * @param string $hwid
     *
     * @return string
     */
    public static function generate($id, $username, $hwid, $loaderVersion = -1): string
    {
        $payload = [
            'id' => $id,
            'name' => $username,
            'hwid' => $hwid,
            'loaderVersion' => $loaderVersion,
            'exp' => time() + 999999999, // Five-minute lifespan
        ];
        return JWT\JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    /**
     * Verify that the provided username and password are legit
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public static function verifyLogin($username, $password): bool
    {
        // Check that username is valid
        if (Db::i()->select('name', 'core_members', "`name`='{$username}'")->count()) {

            // Compare hash for said username to hash of provided password
            $dbHash = Db::i()->select('members_pass_hash', 'core_members', "`name`='{$username}'")->first();
            if (password_verify($password, $dbHash)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a launch parameter token
     *
     * @param int $uid
     * @param int $pid
     * @param string branch
     *
     * @return string
     */
    public static function generateLaunch($uid, $pid, $branch)
    {
        // Generate a unique launch token
        $token = (((($uid * (date('m', time()) * date('i', time()) + 1)) *
                    ($pid * (date('G', time()) * date('d', time()) + 1))) ** 3) ^ 3474759493);
        // Encrypt with AES and hash
        return hash('sha512', AES::encrypt((string)$token ^ $branch, AES_PW_LAUNCH, AES_IV_LAUNCH));
    }

    /**
     * Generate a checksum
     *
     * @param string $data
     *
     * @return string
     */
    public static function generateChecksum($data): string
    {
        // Encrypt $data with AES, then generate a SHA1 hash based off the result
        return hash('sha512', AES::encrypt(json_encode($data), AES_PW_CHECKSUM, AES_IV_CHECKSUM), false);
    }

}