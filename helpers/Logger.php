<?php

// Logger class

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Logger implements Helper
{

    /**
     * @breif Stored log data array
     */
    protected $data = [];

    public static function i(): Logger
    {
        return new Logger();
    }

    /**
     * Logging wizard
     *
     * @param array  $data
     * @param object $payload
     * @param bool   $refine
     *
     * @return bool
     */
    public function log($data, $payload, $refine = true): bool
    {
        // Build default structure
        $s = self::logStruct($data);

        // Check if refine, and refine if so
        if ($refine) {
            $s = self::refineLog($s, $payload);
        }

        // Insert, then return insert response
        return self::insert($s);
    }

    /**
     * Build the default log structure
     *
     * @param $input
     *
     * @return array
     */
    public function logStruct($input): array
    {
        return [
            // AUTO 'log_id'        => @$input['log_id'] ?: null,
            // AUTO 'timestamp'     => @$input['timestamp'] ?: null,
            'user_id' => @$input['user_id'] ?: null,
            'login_user' => @$input['login_user'] ?: null,
            'active_login_attempts' => @$input['active_login_attempts'] ?: null,
            'loader_version' => @$input['loader_version'] ?: null,
            'ip' => @$input['ip'] ?: null,
            'hwid' => @$input['hwid'] ?: null,
            'hardware_information' => @$input['hardware_information'] ?: null,
            'running_software' => @$input['running_software'] ?: null,
            'windows_username' => @$input['windows_username'] ?: null,
            'country' => @$input['country'] ?: null,
            'region_name' => @$input['region_name'] ?: null,
            'city' => @$input['city'] ?: null,
            'exit_reason' => @$input['exit_reason'] ?: null,
            'log_type' => @$input['log_type'] ?: null,
            'cheat_started' => @$input['cheat_started'] ?: null,
            'message' => @$input['message'] ?: null,
            'login_status' => @$input['login_status'] ?: null,
        ];
    }

    /**
     * Refine the provided data into log-suitable stuff
     *
     * @param array  $input
     * @param object $payload
     *
     * @return array
     */
    public function refineLog($input, $payload): array
    {
        // Route through structure to double-check
        $input = $this->logStruct($input);

        // Create the semi-processed structure
        $arr = [
            'user_id' => @$payload->id ?: null,
            'login_user' => Db::i()->select('name', 'core_members', "`member_id`='" . $payload->id . "'")->first(),
            'loader_version' => @$payload->loaderVersion ?: -1,
            'ip' => IP::getUser(),
            'hwid' => @$payload->hwid ?: null,
            'hardware_information' => @$input['hardware_information'] ?: null,
            'running_software' => @$input['running_software'] ?: null,
            'windows_username' => @$input['windows_username'] ?: null,
            'country' => @$input['country'] ?: null,
            'region_name' => @$input['region_name'] ?: null,
            'city' => @$input['city'] ?: null,
            'exit_reason' => @$input['exit_reason'] ?: null,
            'log_type' => @$input['log_type'] ?: null,
            'cheat_started' => @$input['cheat_started'] ?: null,
            'message' => @$input['message'] ?: null,
            'login_status' => @$input['login_status'] ?: null,
        ];

        // Process Geo-Location
        if ($geoLocateData = IP::geoLocate(IP::getUser())) {
            $arr['country'] = $geoLocateData->continent_code;
            $arr['region_name'] = $geoLocateData->region_name;
            $arr['city'] = $geoLocateData->city;
        }

        return $arr;
    }

    /**
     * Insert provided input into the log, with applicable error handling
     *
     * @param array $input
     *
     * @return bool
     */
    public function insert($input): bool
    {
        return (bool)Db::i()->insertSingle('client_log', static::logStruct($input));
    }
}