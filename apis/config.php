<?php

// Config mini-api

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Config
 *
 * @param string token    required
 * @param string product  required
 * @param string hwid     required
 * @param string branch   optional
 *
 * @response 405 INVALID_PARAMS
 * @response 200 INTERNAL_ERROR
 * @response 401 UNAUTHORIZED
 * @response 403 BANNED
 * @response 404 NOT_FOUND
 * @response 200 SUCCESS
 */
class Config implements Endpoint
{
    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return Config
     */
    public static function i(): Config
    {
        return new Config();
    }

    /**
     * Config class main execution function
     *
     * @return void
     */
    public function run(): void
    {
        // Check that correct parameters are provided
        Request::i()->checkParams(['token', 'product', 'hwid']);

        // Validate the provided credentials
        $payload = User::authTBHA(Request::i()->token, Request::i()->hwid);

        // Allow for eight (8) auth entries every thirty (30) seconds
        User::rateLimit($payload->id, ['attempts' => 8, 'type' => "AUTH"]);

        // Allow for five (5) config entries every thirty (30) seconds
        User::rateLimit($payload->id, ['attempts' => 8, 'type' => "CONFIG"]);


        // Check if user has access to product
        if (!(User::hasProduct($payload->id, Request::i()->product))) {

            // Log error then throw
            Logger::i()->log([
                'log_type' => "CONFIG",
                'message' => 'Invalid_Subscriptions',
                'status' => "FAILURE",
                'hwid' => $payload->hwid,
            ], $payload);


            Errors::Unauthorized();
        }

        // Grab the user's data
        $config = json_decode(Db::i()->select('cbpanel_data', 'core_members', "`member_id`='" . $payload->id .
            "'")->first());

        // Set the branch
        $branch = Request::i()->branch ?: "default";

        // Check if config exists
        if (@gettype($config) == "object") {

            // Compile the product key
            $n = "cb_config_" . Request::i()->product;

            // Defualt is just config_pid, while branches are config_pid_branch
            if ($branch == 'default') {

                // Check if config is set
                if (isset($config->$n)) {

                    Logger::i()->log([
                        'log_type' => "CONFIG",
                        'message' => 'Config_Success',
                        'status' => "SUCCESS",
                        'hwid' => $payload->hwid,
                        'cheat_started' => Request::i()->product . ',default',
                    ], $payload);

                    API::i($config->$n)->output();

                }

            } else {

                // Check if user can access branch / if it exists
                if (in_array($branch, User::branches($payload->id))) {

                    // Append the branch
                    $n .= "_{$branch}";

                    // Check if config is set
                    if (isset($config->$n)) {

                        Logger::i()->log([
                            'log_type' => "CONFIG",
                            'message' => 'Config_Success',
                            'status' => "SUCCESS",
                            'hwid' => $payload->hwid,
                            'cheat_started' => Request::i()->product . ',' . $branch,
                        ], $payload);

                        API::i($config->$n)->output();

                    }

                } else {

                    // Log that user is trying to load an unowned branch, possible tampering
                    Logger::i()->log([
                        'log_type' => "CONFIG",
                        'message' => 'Unauthorized_Branch',
                        'status' => "FAILURE",
                        'hwid' => $payload->hwid,
                        'cheat_started' => Request::i()->product . ',' . $branch,
                    ], $payload);

                    Errors::Unauthorized();
                }
            }
        }

        // Log invalid config
        Logger::i()->log([
            'log_type' => "CONFIG",
            'message' => 'Config_Invalid',
            'status' => "FAILURE",
            'hwid' => $payload->hwid,
            'cheat_started' => Request::i()->product . ',' . $branch,
        ], $payload);


        Errors::NotFound();
    }
}