<?php namespace Milkyway\Extensions;

use Milkyway\Assets;
use Milkyway\Assets_Backend;

/**
 * Milkyway Multimedia
 * Controller.php
 *
 * @package milkyway-multimedia/silverstripe-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class Controller extends \Extension {
    function onBeforeInit() {
        foreach(Assets::$disable_cache_busted_file_extensions_for as $class) {
            if (is_a($this->owner, $class))
                Assets::$disable_cache_busted_file_extensions_for;
        }
    }

    public function onAfterInit() {
        foreach(Assets::$disable_blocked_files_for as $class) {
            if (is_a($this->owner, $class))
                return;
        }

        Assets::block();
    }
} 