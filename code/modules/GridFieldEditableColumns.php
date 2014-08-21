<?php
/**
 * Milkyway Multimedia
 * GridFieldEditableColumns.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Modules;

if(class_exists('\GridFieldEditableColumns')) {
	class GridFieldEditableColumns extends \GridFieldEditableColumns
	{
		public function handleSave(\GridField $grid, \DataObjectInterface $record) {
			$list  = $grid->getList();
			$value = $grid->Value();

			if(!isset($value['GridFieldEditableColumns']) || !is_array($value['GridFieldEditableColumns'])) {
				return;
			}

			$form = $this->getForm($grid, $record);

			foreach($value['GridFieldEditableColumns'] as $id => $fields) {
				if(!is_numeric($id) || !is_array($fields)) {
					continue;
				}

				$item = $list->byID($id);

				if(!$item || !$item->canEdit()) {
					continue;
				}

				$extra = array();

				$form->loadDataFrom($fields, \Form::MERGE_CLEAR_MISSING);
				$form->saveInto($item);

				if($list instanceof \ManyManyList) {
					$extra = array_intersect_key($form->getData(), (array) $list->getExtraFields());
				}

				$item->write();
				$list->add($item, $extra);

				if($item->hasExtension('Versioned')) {
					$item->invokeWithExtensions('onBeforePublish', $item);
					$item->publish('Stage', 'Live');
					$item->invokeWithExtensions('onAfterPublish', $item);
				}
			}
		}
	}
}