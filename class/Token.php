<?php

class Token
{
    public static function init($user, $ip)
    {
        $key = md5(uniqid());
        $query = Database::query("INSERT INTO `sessions` (`key`, userId, ip, active) VALUES (?, ?, ?, ?)",
            [$key, $user, $ip, time()]);
        if ($query) {
            return $key;
        }
        return false;
    }
    public static function validate($key, $user, $ip)
    {
        $query = Database::queryFetch("SELECT * FROM `sessions` WHERE `key` = ? AND `userId` = ? AND `ip` = ? LIMIT 1",
            [$key, $user, $ip]);
        if (count($query)) {
            $active = Database::query("UPDATE `sessions` SET `active` = ? WHERE `key` = ?", [time(), $key]);
            return true;
        }
        return false;
    }
    public static function destroy($key)
    {
        $query = Database::query("DELETE FROM `sessions` WHERE `key` = ?", [$key]);
        if ($query) {
            return true;
        }
        return false;
    }
    public static function destroyAll($user)
    {
        $query = Database::query("DELETE FROM `sessions` WHERE `userId` = ?", [$user]);
        if ($query) {
            return true;
        }
        return false;
    }
}

?>