<?php namespace Milkyway\SS\Extensions;

use Milkyway\SS\Assets;
use Milkyway\SS\Assets_Backend;
use Milkyway\SS\Utilities;

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

	    if(Utilities::isFrontendEditingEnabled())
		    \Requirements::unblock(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
    }

    public function getBackLink($fallback = '') {
        if($this->owner->Request) {
            if($this->owner->Request->requestVar('BackURL')) {
                $url = $this->owner->Request->requestVar('BackURL');
            } else if($this->owner->Request->isAjax() && $this->owner->Request->getHeader('X-Backurl')) {
                $url = $this->owner->Request->getHeader('X-Backurl');
            } else if($this->owner->Request->getHeader('Referer')) {
                $url = $this->owner->Request->getHeader('Referer');
            }
        }

        if(!$url)
            $url = $fallback ? $fallback : Director::baseURL();

        return $url;
    }
} 