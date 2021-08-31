<?php

// Logging API

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Log
 *
 * @param string token               required
 * @param string hwid                required
 * @param string windowsUsername     optional
 * @param string cheatStarted        optional
 * @param string runningSoftware     optional
 * @param string hardwareInformation optional
 * @param string exitReason          optional
 * @param string message             optional
 *
 * @response 405 INVALID_PARAMS
 * @response 500 INTERNAL_ERROR
 * @response 401 UNAUTHORIZED
 * @response 403 BANNED
 * @response 400 EXPIRED
 * @response 200 SUCCESS
 */
class Log implements Endpoint
{
    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return Log
     */
    public static function i(): Log
    {
        return new Log();
    }

    /**
     * Run the Auth Mini-API
     *
     * @return void
     */
    public function run(): void
    {
        // Check that required input parameters are set
        Request::i()->checkParams(['token', 'hwid']);

        // Verify and decode token
        $payload = User::authTBHA(Request::i()->token, Request::i()->hwid);

        // Rate limit user
        User::rateLimit($payload->id, ['type' => "LOG"]);

        if (
            (Request::i()->windowsUsername !== null) ||
            (Request::i()->cheatStarted !== null) ||
            (Request::i()->runningSoftware !== null) ||
            (Request::i()->hardwareInformation !== null) ||
            (Request::i()->message !== null) ||
            (Request::i()->exitReason !== null)
        ) {
            // Log the entry
            Logger::i()->log([
                'log_type' => "LOG",
                'windows_username' => Request::i()->windowsUsername,
                'cheat_started' => Request::i()->cheatStarted,
                'running_software' => Request::i()->runningSoftware,
                'hardware_information' => Request::i()->hardwareInformation,
                'message' => Request::i()->message,
                'exit_reason' => Request::i()->exitReason
            ], $payload);

            API::i("SUCCESS")->output();

        } else {

            // Throw params error

            Logger::i()->log(['log_type' => "LOG"], $payload);
            Errors::Params();
        }

    }

}
