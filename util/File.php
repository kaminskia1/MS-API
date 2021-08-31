<?php

// File helper class

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class File
{

    /**
     * Grab all availible file branches for a product, or select existing ones from a predefined array
     *
     * @param int   $pid
     * @param array $branches
     *
     * @return array
     */
    public static function productBranches($pid, $branches = []): array
    {
        global $dl_branches;
        // Cycle through branches
        $res = [];
        foreach ($dl_branches as $k => $v) {
            if (file_exists("uploads/{$pid}/{$k}.exe")) {

                // Filter through provided branches
                if (count($branches) == 0 || in_array($k, $branches)) {
                    array_push($res, $k);
                }
            }
        }
        return $res;
    }

    public static function grabClientFileHex()
    {
        return static::grabFileHex("_client/client.exe");
    }

    /**
     * Grab the file's contents and convert them into hex, then output to API
     *
     * @param $dir
     *
     * @return string
     */
    public static function grabFileHex($dir): string
    {
        // Append uploads/
        $dir = "uploads/{$dir}";
        // Check if file exists
        if (file_exists($dir)) {

            // Send headers
            header('Cache-Control: no-store');

            // Open the file
            $file = fopen($dir, "rb");

            // Write file binary to $read
            $read = fread($file, filesize($dir));

            // Convert binary to hex
            $hex = bin2hex($read);

            // Output the hex
            return $hex;
        }
    }

    /**
     * Fetch and verify the file location for the provided $pid and $branch
     *
     * @param int    $id
     * @param int    $pid
     * @param string $branch
     * @param array  $branches
     *
     * @return string
     */
    public static function resolveProductDirectory($id, $pid, $branch = 'default', $branches = []): string
    {
        // Check if provided pid is numeric
        if (is_numeric($pid)) {

            // Check if product exists
            if (Db::i()->select('p_id', 'nexus_packages', "`p_id`=" . (int)$pid)->count() == 1) {

                // Check if user has active subscription for product
                if (in_array($pid, (array)User::grabActivePlanIDs($id))) {

                    // Check if branch is valid
                    if (is_array($branches[$branch])) {

                        // Check if user's group is in branch
                        $tmp = Db::i()->select('member_group_id, mgroup_others', 'core_members', "`member_id`='{$id}'")->row();
                        $groups = array_merge([(int)$tmp['member_group_id']], explode(",", $tmp['mgroup_others']));
                        if (!empty(array_intersect($branches[$branch], $groups))) {

                            // Return parsed upload path
                            return "{$pid}/{$branch}.exe";
                        }
                    }
                }
            }
        }
        Errors::Params();
    }

    public static function checkClientUpdate($version): bool
    {
        switch ($version) {
            case -1:
            case static::clientVersion():
                return false;
            default:
                return true;
        }
    }

    /**
     * Grab the current client version
     *
     * @return string
     */
    public static function clientVersion(): string
    {
        return (string)("0" ?: Db::i()->execute(Db::i()->staticQuery()->selectClientVersion())->fetch_assoc()['file_version']);
    }
}