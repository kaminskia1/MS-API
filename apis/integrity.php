<?php

// Integrity mini-api

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Integrity
 *
 * @param string token required
 * @param string hwid  required
 * @param int clientVersion optional
 *
 * @response 405 INVALID_PARAMS
 * @response 200 INTERNAL_ERROR
 * @response 401 UNAUTHORIZED
 * @response 403 BANNED
 * @response 400 EXPIRED
 * @response 200 SUCCESS
 */
class Integrity implements Endpoint
{
    /**
     * @breif Freshly-generated JWT token
     */
    private $authToken;

    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return Integrity
     */
    public static function i(): Integrity
    {
        return new Integrity();
    }

    /**
     * Run the Integrity Mini-API
     *
     * @return void
     */
    public function run(): void
    {
        // Check if params set
        Request::i()->checkParams(['token', 'hwid']);

        // Authenticate and set class globals
        $payload = User::authTBHA(Request::i()->token, Request::i()->hwid);

        // Generate new JWT token
        $this->authToken = Crypto::generate($payload->id, Request::i()->username, Request::i()->hwid);

        // Send output
        API::i([
            'Update' => File::checkClientUpdate(Request::i()->loaderVersion ?: -1),
            'Status' => API::i()->loadMiniAPI('status')->compileStatus(),
            'Version' => File::clientVersion(),
            'Time' => time(),
            'User' => User::userData(
                $payload->id,
                $payload->name,
                Request::i()->token,
                $payload->hwid
            ),
        ], 200)->output();

    }

}