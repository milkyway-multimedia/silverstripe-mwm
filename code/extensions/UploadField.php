<?php
/**
 * Milkyway Multimedia
 * UploadField.php
 *
 * @package rugwash.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Extensions;

class UploadField extends \Extension {
	private static $allowed_actions = [
		'index',
	];

	public function beforeCallActionHandler($request, $action) {
		if($this->owner->hasClass('ss-upload-to-folder'))
			$this->setFolderFromRequest($request);
	}

	public function index($request) {
		return $this->owner->FieldHolder();
	}

	protected function setFolderFromRequest($request) {
		if(($folder = $this->getFolderFromRequest($request)) && $folder->canView()) {
			$path = strpos($folder->RelativePath, ASSETS_DIR . '/') === 0 ? substr($folder->RelativePath, strlen(ASSETS_DIR . '/')) : $folder->RelativePath;
			$this->owner->FolderName = $path;
		}
	}

	protected function getFolderFromRequest($request) {
		$folderId = $request->getVar('folder');
		return $folderId ? \Folder::get()->byID($folderId) : null;
	}
} 