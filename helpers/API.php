<?php

// API Class

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class API implements Helper
{

    public $data;

    public $responseCode = 200;

    /**
     * Initiate the API class, so that we can call it dynamically as static
     *
     * @param array $data
     *
     * @return API
     */
    static public function i($data = [], $responseCode = 200): API
    {
        $API = new API();
        $API->data = $data;
        $API->responseCode = $responseCode;
        return $API;

    }

    /**
     * "Run" the API
     *
     * @return void
     */
    public function run(): void
    {
        // Check request type
        if (!(defined("DISABLE_METHOD_FORCE") && @DISABLE_INC_ETE == true)) {
            if (!($_SERVER['REQUEST_METHOD'] === 'POST')) {
                Errors::Request();
            }
        }

        // Check if User Agent filtering is enabled
        if (!(defined("DISABLE_UA_LOCK") && @DISABLE_UA_LOCK == true)) {

            // Import the list
            global $allowed_useragents;

            // Verify the User Agent
            if (!(in_array($_SERVER['HTTP_USER_AGENT'], $allowed_useragents)) && count($allowed_useragents) > 0) {
                Errors::Request();
            }
        }

        // Check if path was provided
        if (Request::i()->exists('path')) {

            // Attempt to load the path
            if ($this->loadMiniAPI()) {
                ;

                // Interpret the path class and attempt to run it
                (Request::i()->path)::i()->run();

            } else {

                Errors::NotFound();

            }
        } else {

            Errors::NotFound();

        }
    }

    /**
     * Load a mini-api, include and return said class to allow for less code
     *
     * @param string $api
     *
     * @return mixed
     */
    public function loadMiniAPI($api = "_UNSET")
    {
        // Import valid API paths
        global $valid_paths;

        // Check if path was provided or not
        if ($api == "_UNSET") {
            $api = Request::i()->path;
        }

        // Check if path is valid
        if (in_array($api, $valid_paths)) {

            // Include path
            @require_once("apis/" . $api . ".php");

            // Check if mini api class loaded right
            if (class_exists($api)) {

                return new $api;
            }
        }
        return false;
    }

    /**
     * Send RESTful output to client
     *
     * @return void
     */
    public function output(): void
    {
        // Create output
        $outputData = json_encode((object)[
            'data' => $this->data,
            'checksum' => Crypto::generateChecksum($this->data),
        ], JSON_PRETTY_PRINT);

        // Check if End to End encryption is enabled
        if (!(defined('DISABLE_OUT_ETE') && @DISABLE_OUT_ETE == true)) {
            // Encrypt the JSON
            $outputData = AES::encrypt($outputData, AES_PW_ENDTOEND, AES_IV_ENDTOEND);
        }

        // Clean away any random echos or errors
        ob_clean();

        // Set default JSON headers and send HTTP response code
        http_response_code($this->responseCode);
        header("Date: " . time());
        header('Content-Type: application /json');
        header_remove("X-Powered-By");
        header_remove("Server");


        // Open the output stream
        $output = fopen('php://output', 'w');

        // Write data to stream
        fwrite($output, $outputData);

        // Close access to output stream
        fclose($output);

        // Complete the run
        exit;

    }

}