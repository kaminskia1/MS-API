<?php

// Update mini-api

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Update
 *
 * @param string token required
 * @param string hwid required
 *
 * @response 405 INVALID_PARAMS
 * @response 200 INTERNAL_ERROR
 * @response 403 UNAUTHORIZED
 * @response 403 BANNED
 * @response 300 EXPIRED
 * @response 200 SUCCESS
 */
class Update implements Endpoint
{
    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return Update
     */
    static public function i(): Update
    {
        return new Update();
    }

    /**
     * Run the Update Mini-API
     *
     * @return void
     */
    public function run(): void
    {

        // Check provided parameters
        Request::i()->checkParams(['token', 'hwid']);

        // Decode the token
        $payload = User::authTBHA(Request::i()->token, Request::i()->hwid);

        // Rate limit
        User::rateLimit($payload->id, ['type' => 'UPDATE']);

        // Check if client version is up to date
        if (((string)$payload->clientVersion != (string)File::clientVersion()) && $payload->clientVersion != -1) {

            // Log cheat download
            Logger::i()->log([
                'log_type' => "UPDATE",
                'message' => 'Update_Success',
                'login_status' => "SUCCESS",
                'software_started' => 'client',
            ], $payload);


            // Convert file into hex and output it
            API::i(['update' => true, 'src' => File::grabClientFileHex()], 200)->output();
        }

        Logger::i()->log([
            'log_type' => "UPDATE",
            'software_started' => 'client',
            'message' => 'Update_Failure',
            'login_status' => "FAILURE",
        ], $payload);

        API::i(['update' => false], 200)->output();


    }
}