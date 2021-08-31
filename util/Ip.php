<?php

// IP Utility

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class IP
{

    /**
     * Grab the current visitor's IP address
     *
     * @return mixed
     */
    static public function getUser(): string
    {
        // ur damn right this is pasted
        // Check for shared Internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && static::validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // Check for IP addresses passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            // Check if multiple IP addresses exist in var
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($iplist as $ip) {
                    if (static::validate_ip($ip)) {
                        return $ip;
                    }
                }
            } else {
                if (static::validate_ip($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED']) && static::validate_ip($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && static::validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && static::validate_ip($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED']) && static::validate_ip($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        }

        // Return unreliable IP address since all else failed
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Validate the IP
     *
     * @param string $ip
     *
     * @return bool
     */
    static private function validate_ip($ip): bool
    {

        if (strtolower($ip) === 'unknown') {
            return false;
        }

        // Generate IPv4 network address
        $ip = ip2long($ip);

        // If the IP address is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {
            // Make sure to get unsigned long representation of IP address
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);

            // Do private network range checking
            if ($ip >= 0 && $ip <= 50331647) {
                return false;
            }
            if ($ip >= 167772160 && $ip <= 184549375) {
                return false;
            }
            if ($ip >= 2130706432 && $ip <= 2147483647) {
                return false;
            }
            if ($ip >= 2851995648 && $ip <= 2852061183) {
                return false;
            }
            if ($ip >= 2886729728 && $ip <= 2887778303) {
                return false;
            }
            if ($ip >= 3221225984 && $ip <= 3221226239) {
                return false;
            }
            if ($ip >= 3232235520 && $ip <= 3232301055) {
                return false;
            }
            if ($ip >= 4294967040) {
                return false;
            }
        }
        return true;
    }

    /**
     * Grab the Geo-Location of an IP
     *
     * @param string $ip
     *
     * @return mixed
     */
    static public function geoLocate($ip)
    {
        // Will switch over to IPS soon but too much work right now
        $req = curl_init("http://api.ipstack.com/{$ip}?access_key=" . IPSTACK_TOKEN);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($req);
        curl_close($req);
        if (@json_decode($data)->success == true) {
            return json_decode($data);
        }
        return false;
    }

}