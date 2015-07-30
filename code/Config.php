<?php namespace Milkyway\SS;

/**
 * @deprecated 0.2 Use Milkyway\SS\Core\Config
 */

use Deprecation;

class Config {
    protected static $depreciation_message = "Please use singleton('env') [recommended] or Milkyway\\SS\\Core\\Config instead";

    public function __construct() {
        Deprecation::notice(0.2, static::$depreciation_message);
    }

    public static function __callStatic($name, $arguments) {
        Deprecation::notice(0.2, static::$depreciation_message);
        return call_user_func_array([singleton('env'), $name], $arguments);
    }
} 