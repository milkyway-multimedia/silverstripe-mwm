<?php
/**
 * Milkyway Multimedia
 * FrontendEditingControllerExtension.php
 *
 * @package relatewell.org.au
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Modules;

class FrontendEditingControllerExtension extends \Extension
{
	private static $allowed_actions = [
		'fesave'
	];

	public function onBeforeInit() {
		$this->original()->onBeforeInit();
	}

	public function fesave(\SS_HTTPRequest $request) {
		if (\Permission::check('ADMIN')) {
			$class = $request->requestVar('feclass');
			$field = $request->requestVar('fefield');
			$id = $request->requestVar('feid');
			$value = $request->requestVar('value');

			if (\Object::has_extension($class, 'Versioned') && $record = \Versioned::get_by_stage($class, 'Live')->byID($id)) {
				$record->$field = $value;
				$record->writeToStage('Stage');
				$record->publish('Stage', 'Live');
			}
			elseif($record = \DataList::create($class)->byID($id)) {
				$record->$field = $value;
				$record->write();
			}

			return $record ? $value : false;
		}

		return $request->requestVar('value');
	}

	protected function original() {
		$ext = new \FrontendEditingControllerExtension();
		$ext->setOwner($this->owner);
		return $ext;
	}
} 