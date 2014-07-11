<?php namespace Milkyway\Extensions;

use Milkyway\Assets;
use Milkyway\Assets_Backend;

/**
 * Milkyway Multimedia
 * Controller.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class Controller extends \Extension {
    function onBeforeInit() {
        if($this->owner instanceof \LeftAndMain)
            \Requirements::set_backend(new \Requirements_Backend());
    }

    public function onAfterInit() {
        if(\Requirements::backend() instanceof Assets_Backend)
            Assets::block();
    }
} 