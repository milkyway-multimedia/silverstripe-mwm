<?php namespace Milkyway\Extensions;

use Milkyway\Assets;

/**
 * Milkyway Multimedia
 * Controller.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class Controller {
    function onBeforeInit() {
        if($this->owner instanceof \LeftAndMain)
            Requirements::set_backend(new Requirements_Backend());
    }

    public function onAfterInit() {
        Assets::block();
    }
} 