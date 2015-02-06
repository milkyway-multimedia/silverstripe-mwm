<?php
/**
 * Milkyway Multimedia
 * UploadField_SelectHandler.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Overrides;

class UploadField_SelectHandler extends \UploadField_SelectHandler {
	protected function getListField($folderID) {
		$field = parent::getListField($folderID);

		if(($this->parent instanceof \UploadField) && $this->parent->getConfig('listDataClass')) {
			if(($files = $field->fieldByName('Files')))
				$files->setList(\DataList::create($this->parent->getConfig('listDataClass'))->filter('ParentID', $folderID));
		}

		parent::extend('updateListField', $field, $folderID);

		return $field;
	}
} 