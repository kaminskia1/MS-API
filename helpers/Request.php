<?php

// Request class

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Request implements Helper
{

    /**
     * @breif Unfiltered request items
     */
    protected $_rawItems = [];

    /**
     * @breif Filtered request items
     */
    protected $_filteredItems = [];

    /**
     * Request constructor.
     *
     * @return void
     */
    public function __construct()
    {
        // Check if ETE encryption is enabled, only grabs and processes POST data
        if (!(defined("DISABLE_INC_ETE") && @DISABLE_INC_ETE == true)) {

            // Decode the input stream
            $stream = @AES::decrypt(@file_get_contents("php://input"), AES_PW_ENDTOEND, AES_IV_ENDTOEND);

            // Explode values into arr a=2&b=3 => [a=2,b=3]
            $items = explode('&', $stream);

            // Check to see if any exist
            if (count($items) > 0) {

                // Filter through each
                foreach ($items as $item) {

                    // Explode into key-value; a=2 => [a => 2]
                    $keyval = explode("=", $item);

                    // Check to make sure it's valid syntax
                    if (count($keyval) == 2) {

                        // Push to raw items arr
                        $this->_rawItems[$keyval[0]] = $keyval[1];

                    } else {
                        Errors::Params();
                    }
                }
            }
        } else {

            // Use default php request stream, includes provided GET params.
            // Encryption should never be disabled on a production build
            $this->_rawItems = $_REQUEST;
        }
    }

    /**
     * Request initializer
     *
     * @return Request
     */
    static public function i(): Request
    {
        $request = new Request();

        // Filter the provided GET/POST parameters and filter them
        foreach ($request->_rawItems as $d => $v) {

            // Sanitize le input
            $new_d = $request->filter($d);
            $request->$new_d = $request->filter($v);

            array_merge($request->_filteredItems, [$d => $v]);
        }

        return $request;
    }

    /**
     * Filter provided item
     *
     * @param $item
     *
     * @return string
     */
    protected function filter($item): string
    {
        // Remove all characters that aren't a-z, A-Z, 0-9, or periods
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $item);
    }

    /**
     * Catch errors if requested variable is invalid
     *
     * @param mixed $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $name;
        } else {
            return null;
        }
    }

    /**
     * Check if provided @item exists in global requests
     *
     * @param $item
     *
     * @return bool
     */
    public function exists($item): bool
    {
        if (@$this->_rawItems[$item] != null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if all provided keys exist in the request scope, throw INVALID_PARAMS if invalid
     *
     * @param $array
     *
     * @return void
     */
    public function checkParams($array)
    {
        foreach ($array as $item) {
            if (@$this->_rawItems[$item] == null) {
                Errors::Params();
            }
        }
    }

}