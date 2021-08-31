<?php

// Download mini-api

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Download
 *
 * @param string token    required
 * @param integer product required
 * @param integer branch  optional
 * @param string hwid     required
 *
 * @response 405 INVALID_PARAMS
 * @response 200 INTERNAL_ERROR
 * @response 403 UNAUTHORIZED
 * @response 403 BANNED
 * @response 400 EXPIRED
 * @response 200 SUCCESS
 */
class Download implements Endpoint
{

    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return Download
     */
    public static function i(): Download
    {
        return new Download();
    }

    /**
     * Download class main execution function
     *
     * @return void
     */
    public function run(): void
    {
        // Check if params set
        Request::i()->checkParams(['token', 'product', 'hwid']);

        // Authenticate
        $payload = User::authTBHA(Request::i()->token, Request::i()->hwid);

        // Rate limit
        User::rateLimit($payload->id, ['attempts' => 3, 'type' => "DOWNLOAD", 'duration' => 90]);

        // Rate limit XSS
        User::rateLimit($payload->id, ['attempts' => 2, 'type' => "DOWNLOAD", 'duration' => 600, 'message' => "Download_XSS"]);

        // Double-check for directory traversal attacks (Even though they should have been filtered by now. Sloppy but works
        if (strpos(Request::i()->product, '/') != false) {

            Logger::i()->log([
                'log_type' => "DOWNLOAD",
                'message' => 'Download_XSS',
                'status' => "FAILURE",
                'cheat_started' => Request::i()->product . ',' . Request::i()->branch,
            ], $payload);

            Errors::Params();
        }
        if (strpos(Request::i()->product, '.') != false) {

            Logger::i()->log([
                'log_type' => "DOWNLOAD",
                'message' => 'Download_XSS',
                'status' => "FAILURE",
                'cheat_started' => Request::i()->product . ',' . Request::i()->branch,
            ], $payload);

            Errors::Params();
        }
        if (strpos(Request::i()->branch, '/') != false) {

            Logger::i()->log([
                'log_type' => "DOWNLOAD",
                'message' => 'Download_XSS',
                'status' => "FAILURE",
                'cheat_started' => Request::i()->product . ',' . Request::i()->branch,
            ], $payload);

            Errors::Params();
        }
        if (strpos(Request::i()->branch, '.') != false) {

            Logger::i()->log([
                'log_type' => "DOWNLOAD",
                'message' => 'Download_XSS',
                'status' => "FAILURE",
                'cheat_started' => Request::i()->product . ',' . Request::i()->branch,
            ], $payload);

            Errors::Params();
        }


        // Filter request variables a second time and import global branches
        global $dl_branches;
        $branches = $dl_branches;
        $product = preg_replace('/[^0-9]/', '', Request::i()->product);
        $branch = preg_replace('/[^a-zA-Z]/', '', Request::i()->branch);

        // Log cheat download
        Logger::i()->log([
            'log_type' => "DOWNLOAD",
            'message' => 'Download_Success',
            'status' => "SUCCESS",
            'cheat_started' => $product . ',' . $branch,
        ], $payload);


        // Convert file into hex from generated directory and pushes it to output
        API::i(
            File::grabFileHex(
                File::resolveProductDirectory(
                    $payload->id,
                    $product,
                    $branch ?: 'default',
                    $branches
                )
            ),
            200
        )->output();


    }

}