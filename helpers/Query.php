<?php

// Static query class

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Query extends Db
{

    public function selectUserByName($username)
    {
        return "SELECT `member_id`, `name`, `members_pass_hash`, `temp_ban`, `client_ban_message`, `client_hwid` FROM core_members WHERE `name`='{$username}'";
    }

    public function selectUserByID($id)
    {
        return "SELECT `name`, `temp_ban`, `client_hwid` FROM `core_members` WHERE `member_id`={$id}";
    }

    public function selectUserPlansByID($id)
    {
        return "SELECT ps_item_id, ps_active, ps_start, ps_expire FROM nexus_purchases WHERE `ps_member`={$id} AND from_unixtime(ps_start) < CURRENT_TIMESTAMP AND from_unixtime(ps_expire) > CURRENT_TIMESTAMP";
    }

    public function selectClientVersion()
    {
        return "SELECT `file_version` FROM downloads_files WHERE file_id=1";
    }

    public function selectPackageInfo()
    {
        return "SELECT `p_id`, `p_name`, `p_cbpanel_data` FROM nexus_packages p_id";
    }

    public function selectUsernameFromID($id)
    {
        return "SELECT name FROM `core_members` WHERE `member_id`={$id}";
    }

    public function updateBanUser($uid, $banInfo)
    {
        return "UPDATE `core_members` SET `temp_ban`={$banInfo['banState']},`client_ban_message`='{$banInfo['banMessage']}',`member_group_id`=8 WHERE `member_id`={$uid}";
    }

    public function selectLastLoginAttemptByUsername($username)
    {
        return "SELECT TIMESTAMPDIFF(SECOND, timestamp, NOW()) FROM `client_log` WHERE `active_login_attempts` in (0, 1)  and `login_user`='{$username}' ORDER BY `timestamp` DESC LIMIT 1";
    }

    public function selectLastLoginAttemptByID($id)
    {
        return "SELECT TIMESTAMPDIFF(SECOND, timestamp, NOW()) FROM `client_log` WHERE `active_login_attempts` in (0, 1)  and `user_id`='{$id}' ORDER BY `timestamp` DESC LIMIT 1";
    }

    public function selectLoginAttemptsByUsername($username)
    {
        return "SELECT count(active_login_attempts) FROM `client_log` WHERE `active_login_attempts` > 0 AND `login_user`='{$username}' AND timestamp >= (SELECT `timestamp` FROM `client_log` WHERE `active_login_attempts` in(0, 1) and `login_user`='{$username}' ORDER BY `timestamp` DESC LIMIT 1)";
    }

    public function selectLoginAttemptsByID($id)
    {
        return "SELECT count(active_login_attempts) FROM `client_log` WHERE `active_login_attempts` > 0 AND `user_id`='{$id}' AND timestamp >= (SELECT `timestamp` FROM `client_log` WHERE `active_login_attempts` in(0, 1) and `user_id`='{$id}' ORDER BY `timestamp` DESC LIMIT 1)";
    }

    public function selectUserID($username)
    {
        return "SELECT `member_id` FROM `core_members` WHERE `name`='{$username}'";
    }

    public function updateUserHWIDByID($id, $hwid)
    {
        return "UPDATE `core_members` SET `client_hwid`='{$hwid}' WHERE `member_id`='{$id}'";
    }

    public function updateUserHWIDByUsername($name, $hwid)
    {
        return "UPDATE `core_members` SET `client_hwid`='{$hwid}' WHERE `name`='{$name}'";
    }

}
