<?php

// Import IPS
require_once("../init.php");

// Define global to prevent wrong file inclusion
define("CFG_LOADED", true);

// Global includes, need to include these before IPS check because of import encryption
require_once("include.php");

// Check if we are using invision
if (isset(Request::i()->path)) {

    // Initiate runtime
    API::i()->run();

} else {

    // Not found because no mini-api is provided
    Errors::NotFound();

}