<?php namespace Milkyway\SS;

use Milkyway\SS\Core\Requirements;
use Milkyway\SS\Core\RequirementsBackend;
use Deprecation;

/**
 * @deprecated 0.2 Use Milkyway\SS\Core\Requirements
 */

class Assets extends Requirements
{
    public function __construct()
    {
        Deprecation::notice(0.2, "Assets has been renamed to Requirements, please use singleton('require') [recommended] or Milkyway\\SS\\Core\\Requirements instead");
    }
}

/**
 * @deprecated 2.0 Use Milkyway\SS\Core\RequirementsBackend
 */

class Assets_Backend extends RequirementsBackend
{
    public function __construct()
    {
        Deprecation::notice(0.2, "Assets_Backend has been renamed to RequirementsBackend, please use Milkyway\\SS\\Core\\RequirementsBackend instead");
    }
}
