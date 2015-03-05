<?php
/**
 * Milkyway Multimedia
 * LeftAndMain.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Extensions;


use Milkyway\SS\Assets;

class LeftAndMain extends \LeftAndMainExtension {
	private static $allowed_actions = [
		'annihilate',
		'publish_record',
		'publish-record',
		'unpublish_record',
		'unpublish-record',
	];

	function onAfterInit() {
        foreach((array)$this->owner->config()->include_requirements_from_class as $class => $method) {
            if(is_numeric($class))
                singleton($method)->includes($this->owner);
            else
                singleton($class)->$method($this->owner);
        }

		\Requirements::javascript(SS_MWM_DIR . '/javascript/mwm.admin.js');
		Assets::block_ajax('htmlEditorConfig');
		Assets::block_ajax('googlesuggestfield-script');
	}

	public function publish_record($request) {
		$record = $this->recordFromRequest($request);
		$isVersioned = $record->hasExtension('Versioned');

		if($isVersioned && $record->hasMethod('canPublish') && !$record->canPublish())
			return \Security::permissionFailure($this->owner);
		elseif(!$record->canEdit())
			return \Security::permissionFailure($this->owner);

		try {
			if ($isVersioned) {
				if($record->hasMethod('doPublish'))
					$record->doPublish();
				else
					$record->publish('Stage', 'Live');
			}
			else
				$record->write();
		} catch (\Exception $e) {
			return $this->respondWithMessage(false, $e->getMessage(), 401);
		}

		$message = rawurlencode(_t(
			'CMSMain.PUBLISHED_PAGE',
			"Published '{title}'",
			['title' => $record->Title]
		));

		return $this->respondWithMessage(true, $message, 200);
	}

	public function unpublish_record($request) {
		$record = $this->recordFromRequest($request);
		$isVersioned = $record->hasExtension('Versioned');

		if($isVersioned && $record->hasMethod('canDeleteFromLive') && !$record->canDeleteFromLive())
			return \Security::permissionFailure($this->owner);
		elseif(!$record->canEdit())
			return \Security::permissionFailure($this->owner);

		try {
			if ($isVersioned) {
				if($record->hasMethod('doUnpublish'))
					$record->doUnpublish();
				else {
					$origStage = \Versioned::current_stage();
					\Versioned::reading_stage('Live');

					// This way our ID won't be unset
					$clone = clone $record;
					$clone->delete();

					\Versioned::reading_stage($origStage);
				}
			}
			else
				$record->write();
		} catch (\Exception $e) {
			return $this->respondWithMessage(false, $e->getMessage(), 401);
		}

		$message = rawurlencode(_t(
			'CMSMain.UNPUBLISHED_PAGE',
			"Unpublished '{title}'",
			['title' => $record->Title]
		));

		return $this->respondWithMessage(true, $message, 200);
	}

	public function annihilate($request) {
		$record = $this->recordFromRequest($request);
		$isVersioned = $record->hasExtension('Versioned');

		$clone       = null;
		$title       = $record->Title ? $record->Title : 'Record #' . $record->ID;
		$id = $record->ID;

		try {
			if ($isVersioned)
				$clone = clone $record;

			$record->delete();

			if ($clone) {
				$clone->deleteFromStage('Live');
				$versions = $clone->baseTable() . '_versions';
				\DB::query("DELETE FROM \"{$versions}\" WHERE \"RecordID\" = '{$clone->ID}'");
			}
		} catch (\Exception $e) {
			return $this->respondWithMessage(false, $e->getMessage(), 401);
		}

		$message = rawurlencode(_t(
			'CMSMain.DELETED_PAGE',
			"Deleted '{title}'",
			['title' => $title]
		));

		return $this->respondWithMessage(true, $message, 200);
	}

	protected function respondWithMessage($success = true, $message = '', $code = 0) {
		if(!$message) {
			if($success) {
				$message = rawurlencode(_t(
					'CMSMain.ACTION_COMPLETE',
					'Done'
				));
			}
			else {
				$message = rawurlencode(_t(
					'CMSMain.ACTION_FAILED',
					'Could not complete action'
				));
			}
		}

		if(!$code) {
			$code = $success ? 200 : 400;
		}

		$this->owner->Response->setStatusCode($code);
		$this->owner->Response->addHeader('X-Status', $message);

		return $this->owner->getResponseNegotiator()->respond($this->owner->Request, [
			'SiteTree' => function(){}
		]);
	}

	protected function recordFromRequest($request) {
		if (!\SecurityToken::inst()->checkRequest($request))
			return $this->respondWithMessage(false);

		$id = $request->param('ID');

		if(!$id || !is_numeric($id))
			return $this->respondWithMessage(false, "Cannot find record with ID: '$id'", 400);

		$class = $this->owner->config()->tree_class ?: 'SiteTree';
		$record = \DataObject::get_by_id($class, $id);

		if (!$record || !$record->ID)
			return $this->respondWithMessage(false, "Bad record ID: '$id''", 404);

		return $record;
	}
} 