<?php namespace Milkyway\SS;

/**
 * @deprecated 0.2 Use Milkyway\SS\Core\Director
 */

use Milkyway\SS\Core\Director as Original;

class Director extends Original
{
    public function __construct()
    {
        Deprecation::notice(0.2, "Assets has been renamed to Requirements, please use singleton('require') [recommended] or Milkyway\\SS\\Core\\Requirements instead");
    }
}
