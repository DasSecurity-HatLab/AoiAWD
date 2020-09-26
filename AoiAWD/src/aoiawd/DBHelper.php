<?php
namespace aoiawd;

abstract class DBHelper
{
    static private $dbcountCache = [];

    static public function getCollactionCount($collaction)
    {
        if (!isset(self::$dbcountCache[$collaction])) {
            $count = self::getDB()->$collaction->count();
            self::$dbcountCache[$collaction] = $count;
        }
        return self::$dbcountCache[$collaction];
    }

    static public function addCollactionCount($collaction, $count = 1)
    {
        if (!isset(self::$dbcountCache[$collaction])) {
            $dbcount = self::getDB()->$collaction->count();
            self::$dbcountCache[$collaction] = $dbcount;
        }
        self::$dbcountCache[$collaction] += $count;
    }

    static public function escape($array)
    {
        $new = [];
        foreach ($array as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = self::escape($value);
            }
            $new[str_replace(['$', '.'], ['＄', '．'], $key)] = $value;
        }
        return $new;
    }

    static public function getDB()
    {
        return AoiAWD::getInstance()->getDB();
    }
}
