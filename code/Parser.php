<?php namespace Milkyway\SS;

/**
 * @deprecated 0.2 Use Milkyway\SS\Core\Extras\Parser
 */

use Milkyway\SS\Shortcodes\Extras\TextParser as Original;
use Deprecation;

class Parser extends Original {
    public function __construct($content = '') {
        Deprecation::notice(0.2, "Please use Milkyway\\SS\\Shortcodes\\Extras\\TextParser instead");
        parent::__construct($content);
    }
}