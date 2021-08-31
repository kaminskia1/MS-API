<?php

// User helper class

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class User
{

    /**
     * Grab ID's of the User's current active plans
     *
     * @param int $id
     *
     * @return array
     */
    public static function grabActivePlanIDs($id): array
    {
        // Select active item id's based on provided $id
        $arr = Db::i()->select('`ps_item_id`', 'nexus_purchases', "`ps_member`={$id} AND from_unixtime(ps_start) < CURRENT_TIMESTAMP AND from_unixtime(ps_expire) > CURRENT_TIMESTAMP")->retrieve();

        // Filter through rows and push unique pid's to arr
        $pid = array();
        while ($row = $arr->fetch_assoc()) {
            if (!in_array((int)$row['ps_item_id'], $pid)) {
                array_push($pid, (int)$row['ps_item_id']);
            }
        }
        return $pid;
    }

    /**
     * Build user data structure
     *
     * @param int    $id
     * @param string $username
     * @param string $token
     * @param string $hwid
     *
     * @return object
     */
    public static function userData($id, $username, $token, $hwid): object
    {
        $struct = (object)[
            'ID' => $id,
            'Group' => static::groups($id)[0],
            'Token' => $token,
            'Username' => $username,
            'Branches' => static::branches($id),
            'Plans' => static::grabUserPlans($id, static::branches($id)),
        ];


        return $struct;
    }

    /**
     * Grab group id's for the user
     *
     * @param int $id
     *
     * @return array
     */
    public static function groups($id): array
    {
        // Validate the input
        $ids = [];
        if (is_int($id)) {
            $user = Db::i()->select('`member_group_id`,`mgroup_others`', 'core_members', "`member_id`='{$id}'")->row();
            // Push main group to array
            array_push($ids, (int)$user['member_group_id']);

            // Check if subgroups exist
            if (strlen($user['mgroup_others']) > 0) {
                if (strpos($user['mgroup_others'], ',') != -1) {

                    // Explode string into arr
                    $alts = explode(',', $user['mgroup_others']);

                    // Use foreach so we can convert datatype to string aswell
                    foreach ($alts as $g) {
                        array_push($ids, (int)$g);
                    }

                } else {
                    // Single secondary group, no need to explode
                    array_push($ids, (int)$user['mgroup_others']);
                }
            }
            return $ids;
        } else {
            return $ids;
        }
    }

    /**
     * Grab availible branches for the user
     *
     * @param int $id
     *
     * @return array
     */
    public static function branches($id): array
    {
        // Import branches
        global $dl_branches;

        // Grab groups
        $groups = static::groups($id);

        // Init valid
        $valid = [];

        // Cycle through branches
        foreach ($dl_branches as $k => $v) {

            // Check if groups intersect
            if (array_intersect($v, $groups) || $k == 'default') {

                // Pousser à la matrice, garCON!
                array_push($valid, $k);
            }
        }
        return $valid;
    }

    /**
     * Grab and simplify the user's subscription plans
     *
     * @param int   $id
     * @param array $branches
     *
     * @return object
     */
    public static function grabUserPlans($id, $branches = []): object
    {

        $q = Db::i()->execute(Db::i()->staticQuery()->selectUserPlansByID($id));
        $obj = (object)[];

        // Filter through plans
        while ($row = $q->fetch_assoc()) {
            $id = $row['ps_item_id'];
            $obj->$id = [

                // 0 = Expire time (UNIX Second)
                0 => $row['ps_expire'],

                // 1 = Branch Array
                1 => static::compileBranches(
                    File::productBranches($row['ps_item_id'], $branches),
                    $id,
                    $row['ps_item_id']
                )

            ];

        };
        return $obj;
    }

    /**
     * Generate launch tokens for all provided branches
     *
     * @param array $obj
     * @param int   $id
     * @param int   $pid
     *
     * @return object
     */
    public static function compileBranches($obj, $id, $pid): object
    {
        // Initialize the obj
        $res = (object)[];

        // Cycle through branches and push them to output object
        foreach ($obj as $branch) {
            $res->$branch = Crypto::generateLaunch($id, $pid, $branch);
        }

        return $res;
    }

    /**
     * Authenticate the token, Ban state, HWID, and Active Subscriptions
     *
     * @param string $token
     * @param string $hwid
     *
     * @return mixed
     */
    public static function authTBHA($token, $hwid)
    {
        // Verify and decrypt token
        if ($payload = Crypto::decryptPayload($token)) {

            $attempts = Db::i()->select('COUNT(*)', 'client_log', "`user_id`='" . $payload->id . "' AND `timestamp`>" .
                time() . " AND `message`='Login_Failed'")->first();

            // Rate limit
            User::rateLimit($payload->id, ['attempts' => 8, 'type' => "INTEGRITY"]);

            // Rate limit failed logins
            User::rateLimit($payload->id, ['message' => 'Login_Failure', 'duration' => 300]);

            // Verify that user is not banned
            if (!static::isBanned($payload->id)) {

                // Verify that provided HWID matches stored
                if (User::validateHWID($payload->id, $hwid)) {

                    // Verify that user has subscriptions
                    if (static::hasActiveSubscriptions($payload->id)) {

                        // Passed all checks, return the token payload and log!
                        Logger::i()->log([
                            'log_type' => "INTEGRITY",
                            'windows_username' => Request::i()->windowsUsername ?: null,
                            'message' => "Login_Success",
                            'login_status' => "SUCCESS",
                            'hwid' => Request::i()->windowsUsername ?: null,
                            'active_login_attempts' => $attempts
                        ], $payload);

                        return $payload;

                    } else {

                        // Log invalid subscriptions
                        Logger::i()->log([
                            'log_type' => "INTEGRITY",
                            'windows_username' => Request::i()->windowsUsername ?: null,
                            'message' => 'Invalid_Subscriptions',
                            'login_status' => "FAILURE",
                            'hwid' => $hwid,
                            'active_login_attempts' => $attempts
                        ], $payload);


                        Errors::Unauthorized();
                    }
                } else {

                    // Log HWID mismatch
                    Logger::i()->log([
                        'log_type' => "INTEGRITY",
                        'windows_username' => Request::i()->windowsUsername ?: null,
                        'message' => 'HWID_Mismatch',
                        'login_status' => "FAILURE",
                        'hwid' => $hwid,
                        'active_login_attempts' => $attempts
                    ], $payload);

                    // Invalid HWID, ban user for acc sharing
                    Db::i()->execute(Db::i()->staticQuery()->updateBanUser($payload->id, "You have been banned! 0x5"));

                    // return INTERNAL_ERROR with code 200 to signal HWID,

                    // If you post to auth, receive internal error with 200 and not 500,
                    // make client exit because HWID is invalid. Not good practice to base security around obscurity,
                    // but it can't hurt to have an extra level wherever possible.
                    Errors::HwidBan();

                }
            } else {

                // Log that banned user tried to access
                Logger::i()->log([
                    'log_type' => "INTEGRITY",
                    'windows_username' => Request::i()->windowsUsername ?: null,
                    'message' => 'Banned',
                    'login_status' => "FAILURE",
                    'hwid' => $payload->hwid,
                    'active_login_attempts' => $attempts,
                    'exit_reason' => $hwid,
                ], $payload);

                Errors::Banned();
            }
        } else {

            // We can't log here because we don't have a user ID to bind to said entry, because the token is
            // invalid/could not be decoded
            Errors::Unauthorized();
        }
    }

    /*
     * This is where it turns into a cluster fuck
     */

    /**
     * Rate limit the current user
     *
     * @param int   $id
     * @param array $data
     *
     * @struct
     * $data = [
     *  'duration' => 30,
     *  'type' => null,
     *  'message' => null
     *  'attempts' => 3,
     * ]
     * @endstruct
     * @return void
     */
    public static function rateLimit($id, $data = []): void
    {
        if (!(defined('DISABLE_RATE_LIMIT') && @DISABLE_RATE_LIMIT == true)) {

            // Compile where clause
            $where = ("`user_id`='$id' AND `timestamp`>FROM_UNIXTIME(") . (time() - (@$data['duration'] ?: 30)) . ")" .
                (@$data['type'] ? (" AND `log_type`='{$data['type']}'") : null) .
                (@$data['message'] ? (" AND `message`='{$data['message']}'") : null);

            // Grab count, and see if it is less than or equal to provided maximum
            if ((int)Db::i()->select("COUNT(*)", "client_log", $where)->first() > (@$data['attempts'] ?: 3)) {
                Errors::RateLimit();
            }
        }
    }

    /**
     * Check to make sure that the logging in user is not already banned
     *
     * @param int $id
     *
     * @return bool
     */
    public static function isBanned($id): bool
    {
        return (bool)Db::i()->select('temp_ban', 'core_members', "`member_id`=" . $id)->first();
    }

    /**
     * Check to make sure that the provided HWID matches the stored HWID
     *
     * @param int    $id
     * @param string $hwid
     *
     * @return bool
     */
    public static function validateHWID($id, $hwid): bool
    {
        // Check for duplicates
        $hwids = Db::i()->select('client_hwid', 'core_members', "`member_id`=" . $id);
        if ($hwids->count() > 1) {
            Db::i()->update('core_members', "`temp_ban`=1,`client_ban_message`='You have been banned! Reason: 0x5'", "`client_hwid`='{$hwid}'");
            return false;
        }

        // Still going? Grab user's stored HWID
        $oldHWID = Db::i()->select('client_hwid', 'core_members', "`member_id`=" . $id)->first();

        // Check if equal to stored, return true if so
        if ($oldHWID == $hwid) {
            return true;
        } else {
            if ($oldHWID == (0 || '0' || null || '')) {
                // Check if user's stored HWID is 'unset', update it if so
                Db::i()->update('core_members', "`client_hwid`='" . $hwid . "'", "`member_id`=" . $id);
                return true;
            } else {
                // Invalid HWID, return false and ban
                Db::i()->update('core_members', "`temp_ban`=1,`client_ban_message`='You have been banned! Reason: 0x5'", "`client_hwid`='{$hwid}'");
                return false;
            }
        }
    }

    /**
     * Check if user has active subscriptions
     *
     * @param int $id
     *
     * @return bool
     */
    public static function hasActiveSubscriptions($id): bool
    {
        // Grab number of rows and convert to bool
        return (bool)Db::i()->execute(Db::i()->staticQuery()->selectUserPlansByID($id))->num_rows;
    }

    /**
     * Authenticate the login, Ban state, HWID, and Active Subscriptions
     *
     * @param string $username
     * @param string $password
     * @param string $hwid
     *
     * @return mixed
     * @internal
     */
    public static function authLBHA($username, $password, $hwid)
    {
        // Grab UID
        $id = (int)Db::i()->select('member_id', 'core_members', "`name`='" . Request::i()->username . "'")->first();

        // Rate limit attempts
        User::rateLimit($id, ['attempts' => 8, 'type' => "AUTH"]);

        // Rate limit failed logins
        User::rateLimit($id, ['message' => 'Login_Failure', 'duration' => 300]);

        // Grab attempts to limit DB queries
        $attempts = Db::i()->select('COUNT(*)', 'client_log', "`user_id`='$id' AND `timestamp`>" . time() .
            " AND `message`='Login_Failed'")->first();

        // Verify and decrypt token
        if (Crypto::verifyLogin($username, $password)) {

            // Verify that user is not banned
            if (!static::isBanned($id)) {

                // Verify that provided HWID matches stored
                if (User::validateHWID($id, $hwid)) {

                    // Verify that user has subscriptions
                    if (static::hasActiveSubscriptions($id)) {

                        // Passed all checks, return the token payload and log!
                        Logger::i()->log([
                            'log_type' => "AUTH",
                            'windows_username' => Request::i()->windowsUsername ?: null,
                            'message' => "Login_Success",
                            'login_status' => "SUCCESS",
                            'hwid' => Request::i()->windowsUsername ?: null,
                            'active_login_attempts' => $attempts
                        ], (object)['id' => $id, 'username' => $username, 'clientVersion' => Request::i()->clientVersion ?: -1, 'hwid' => $hwid]);

                        return $id;

                    } else {

                        // Log invalid subscriptions
                        Logger::i()->log([
                            'log_type' => "AUTH",
                            'windows_username' => Request::i()->windowsUsername ?: null,
                            'message' => 'Invalid_Subscriptions',
                            'login_status' => "FAILURE",
                            'hwid' => $hwid,
                            'active_login_attempts' => $attempts
                        ], (object)['id' => $id, 'username' => $username, 'clientVersion' => Request::i()->clientVersion ?: -1, 'hwid' => $hwid]);

                        // Throw l'error
                        Errors::Unauthorized();

                    }

                } else {

                    // Log HWID mismatch
                    Logger::i()->log([
                        'log_type' => "AUTH",
                        'windows_username' => Request::i()->windowsUsername ?: null,
                        'message' => 'HWID_Mismatch',
                        'login_status' => "FAILURE",
                        'hwid' => $hwid,
                        'active_login_attempts' => $attempts
                    ], (object)['id' => $id, 'username' => $username, 'clientVersion' => Request::i()->clientVersion ?: -1, 'hwid' => $hwid]);

                    // Invalid HWID, ban user for acc sharing
                    Db::i()->execute(Db::i()->staticQuery()->updateBanUser($id, "You have been banned! 0x5"));

                    // return INTERNAL_ERROR with code 200 to signal HWID,

                    // If you post to auth, receive internal error with 200 and not 500, make client exit because
                    // HWID is invalid
                    Errors::HwidBan();

                }
            } else {
                Errors::Banned();
            }
        } else {

            // Log failed login attempt
            Logger::i()->log([
                'log_type' => "AUTH",
                'windows_username' => Request::i()->windowsUsername ?: null,
                'message' => "Login_Failure",
                'login_status' => "FAILURE",
                'hwid' => $hwid,
                'active_login_attempts' => $attempts
            ], (object)['id' => $id, 'username' => $username, 'clientVersion' => Request::i()->clientVersion ?: -1, 'hwid' => $hwid]);

            // Throw error
            Errors::Unauthorized();

        }
    }

    /**
     * Check if user has access to specified product
     *
     * @param int $uid
     * @param int $pid
     *
     * @return bool
     */
    public static function hasProduct($uid, $pid): bool
    {
        if (isset(static::grabUserPlans($uid)->$pid)) {
            return true;
        }
        return false;
    }

}