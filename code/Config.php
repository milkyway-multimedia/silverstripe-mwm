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

    public static function get($key, $parseEnvVar = null, $fromCache = true, $doCache = true) {
        if($fromCache && isset(static::$_cache[$key]))
            return static::$_cache[$key];

        $value = null;
        $findInEnvironment = function($key) use($parseEnvVar) {
            $value = null;

            if(isset($_ENV[$key]))
                $value = $_ENV[$key];

            if(getenv($key))
                $value = getenv($key);

            if(is_callable($parseEnvVar))
                $value = call_user_func_array($parseEnvVar, [$value, $key]);

            return $value;
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