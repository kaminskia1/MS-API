<?php

// Auth mini-api

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Auth
 *
 * @param string username        required
 * @param string password        required
 * @param string clientVersion   optional
 * @param string windowsUsername optional
 * @param string hwid            required
 *
 * @response 405 INVALID_PARAMS
 * @response 200 INTERNAL_ERROR
 * @response 401 UNAUTHORIZED
 * @response 403 BANNED
 * @response 200 SUCCESS
 */
class Auth implements Endpoint
{

    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return Auth
     */
    static public function i(): Auth
    {
        // Create a temporary class instance to allow for dynamic functions to be called statically
        return new Auth();
    }

    /**
     * Run the Auth Mini-API
     *
     * @return void
     */
    public function run(): void
    {
        // Check if params set
        Request::i()->checkParams(['username', 'password', 'hwid']);

        // Authenticate and rate limit failed logins
        $id = User::authLBHA(Request::i()->username, Request::i()->password, Request::i()->hwid);

        // Generate JWT token
        $authToken = Crypto::generate($id, Request::i()->username, Request::i()->hwid, Request::i()->loaderVersion ?: -1);

        // Its output time bois
        API::i([
            'Update' => File::checkClientUpdate(Request::i()->clientVersion ?: -1),
            'Status' => API::i()->loadMiniAPI('status')->compileStatus(),
            'Time' => time(),
            'User' => User::userData(
                $id,
                Request::i()->username,
                $authToken,
                Request::i()->hwid
            ),
        ], 200)->output();
    }


}