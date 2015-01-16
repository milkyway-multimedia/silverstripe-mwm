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

        $keyParts = explode('.', $key);
        $value = null;

        if(count($keyParts) == 1) {
            if(getenv($keyParts[0]))
                $value = getenv($keyParts[0]);
            elseif(isset($_ENV[$keyParts[0]]))
                $value = $_ENV[$keyParts[0]];
        }
        else {
            $class = array_shift($keyParts);
            $value = static::find_from_parts($class, $keyParts);
        }

        if($doCache)
            static::$_cache[$key] = $value;

        return $value;
    }

    private static function find_from_parts($findIn, $parts = []) {
        $key = array_shift($parts);
        $value = null;

        if(is_array($findIn)) {
            if(isset($findIn[$key]))
                $value = $findIn[$key];
        }
        elseif(!($value = Original::inst()->get($findIn, $key))) {
            if(getenv($findIn . '.' . $key))
                $value = getenv($findIn . '.' . $key);
            elseif(isset($_ENV[$findIn . '.' . $key]))
                $value = $_ENV[$findIn . '.' . $key];
        }

        if(!$value || !is_array($value) || !count($parts))
            return $value;
        else
            return static::find_from_parts($value, $parts);
    }
} 