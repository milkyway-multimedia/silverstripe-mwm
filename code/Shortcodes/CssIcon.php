<?php
/**
 * Milkyway Multimedia
 * CssIcon.php
 *
 * @package milkywaymultimedia.com.au
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shortcodes;


use Milkyway\SS\Utilities;

class CssIcon implements Contract
{
	public function isAvailableForUse($member = null)
	{
		return true;
	}

	public function render($arguments, $caption = null, $parser = null)
	{
		return \ArrayData::create($arguments)->renderWith('GoogleFixURL');
	}

	public function code()
	{
		return ['icon', 'css_icon'];
	}

	public function title()
	{
		return [
			'icon' => _t('Shortcodable.ICON', 'Icon'),
		];
	}

	public function formField()
	{
		if (\HTMLEditorField::config()->valid_icon_shortcodes) {
			$icon = \DropdownField::create(
				'use',
				_t('Shortcodable.ICON', 'Icon'),
				Utilities::map_array_to_i18n(\HTMLEditorField::config()->valid_icon_shortcodes, 'Icon')
			);
		} else {
			$icon = \TextField::create('use', _t('Shortcodable.ICON', 'Icon'));
		}

		if (\HTMLEditorField::config()->prepend_icon) {
			$iconPrepend = \ReadonlyField::create(
				'prepend',
				_t('Shortcodable.ICON_PREPEND', 'Prepend'),
				\HTMLEditorField::config()->prepend_icon
			);
		} else {
			$iconPrepend = \TextField::create(
				'prepend',
				_t('Shortcodable.ICON_PREPEND', 'Prepend')
			)->setAttribute('placeholder', 'fa fa-');
		}

		if (\HTMLEditorField::config()->valid_icon_classes) {
			$iconClasses = \DropdownField::create(
				'classes',
				_t('Shortcodable.ICON_CLASSES', 'Classes'),
				array_combine(
					\HTMLEditorField::config()->valid_icon_classes,
					\HTMLEditorField::config()->valid_icon_classes
				)
			);
		} else {
			$iconClasses = \TextField::create('classes', _t('Shortcodable.ICON_CLASSES', 'Classes'));
		}

		return
			\CompositeField::create(
				\FieldList::create(
					$icon,
					$iconPrepend,
					$iconClasses
				)
			);
	}
} 