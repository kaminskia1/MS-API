<?php

// Permission(ban) API

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Permission
 *
 * @param string token  required
 * @param string reason optional
 *
 * @response 405 INVALID_PARAMS
 * @response 500 INTERNAL_ERROR
 * @response 401 UNAUTHORIZED
 * @response 403 BANNED
 * @response 400 EXPIRED
 * @response 200 SUCCESS
 */
class Permission implements Endpoint
{

    /**
     * @breif Accepted ban-reason values
     */
    public $reasons = [
        '0x0',
        '0x1',
        '0x2',
        '0x3',
        '0x4',
        '0x5'
    ];

    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return Permission
     */
    public static function i(): Permission
    {
        return new Permission();
    }

    /**
     * Run the endpoint
     *
     * @param string $reason
     *
     * @return void
     */
    public function run($reason = '0x5'): void
    {
        // Check if params set
        Request::i()->checkParams(['token']);

        $payload = User::authTBHA(Request::i()->token, Request::i()->hwid);

        // Rate limit
        User::rateLimit($payload->id, ['type' => 'PERMISSION']);

        // Validate reason
        if (in_array(Request::i()->reason, $this->reasons)) {
            $reason = Request::i()->reason;
        }

        // Ban the ID
        if (Db::i()->execute(Db::i()->staticQuery()->updateBanUser($payload->id, $reason))) {

            // Log successful ban
            Logger::i()->log([
                'log_type' => 'PERMISSION',
                'message' => 'User_Banned',
                'login_status' => 'SUCCESS',
                'exit_reason' => $reason,
            ], $payload);

            // Send success message
            API::i('SUCCESS', 200)->output();

        } else {

            // Log the failure
            Logger::i()->log([
                'log_type' => 'PERMISSION',
                'message' => 'Internal_Error',
                'login_status' => 'FAILURE',
                'exit_reason' => $reason,
            ], $payload);

            // Throw error
            Errors::Internal();
        }

    }

}