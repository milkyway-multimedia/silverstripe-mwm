<?php
/**
 * Milkyway Multimedia
 * Config.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS;

use Config as Original;

class Config {
    private static $_cache = [];

    public static function get($key, $fromCache = true, $doCache = true) {
        if($fromCache && isset(static::$_cache[$key]))
            return static::$_cache[$key];

        $value = null;
        $findInEnvironment = function($key) {
            if(isset($_ENV[$key]))
                return $_ENV[$key];

            if(getenv($key))
                return getenv($key);

            return null;
        };

        if(strpos($key, '.')) {
            $keyParts = explode('.', $key);
            $class = array_shift($keyParts);

            $value = array_get((array)Original::inst()->forClass($class), implode('.', $keyParts));

            if(!$value)
                $value = $findInEnvironment($key);

            if(!$value && count($keyParts) && ($first = $findInEnvironment($class)) && is_array($first))
                $value = array_get($first, implode('.', $keyParts));
        }
        else
            $value = $findInEnvironment($key);

        if($doCache)
            static::$_cache[$key] = $value;

        return $value;
    }

    public static function set($key, $value = null) {
        static::$_cache[$key] = $value;
    }
} 