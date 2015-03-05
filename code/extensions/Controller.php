<?php namespace Milkyway\SS\Extensions;

use Milkyway\SS\Assets;
use Milkyway\SS\Assets_Backend;
use Milkyway\SS\Director;
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
                Assets::$use_cache_busted_file_extensions = false;
        }
    }

    public function onAfterInit() {
        foreach(Assets::$disable_blocked_files_for as $class) {
            if (is_a($this->owner, $class))
                return;
        }

        Assets::block_default();

	    if(Utilities::isFrontendEditingEnabled())
		    \Requirements::unblock(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
    }

    public function getBackLink($fallback = '') {
	    $url = '';

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
            $url = $fallback ? $fallback : \Director::baseURL();

        return $url;
    }

	public function displayNiceView($controller = null, $url = '', $action = '') {
		if(!$controller)
			$controller = $this->owner;

		return Director::create_view($controller, $url, $action);
	}

	public function respondToFormAppropriately($params, $form = null, $redirect = ''){
		if($redirect && !isset($params['redirect']))
			$params['redirect'] = $redirect;

		if($this->owner->Request->isAjax()) {
			if(!isset($params['code']))
				$params['code'] = 200;
			if(!isset($params['code']))
				$params['status'] = 'success';

			return Director::ajax_response($params, $params['code'], $params['status']);
		}
		else {
			if(isset($params['redirect']))
				$this->owner->redirect($params['redirect']);

			if($form && isset($params['message'])) {
				$form->sessionMessage($params['message'], 'good');
			}

			if(!$this->owner->redirectedTo())
				$this->owner->redirectBack();
		}
	}
} 