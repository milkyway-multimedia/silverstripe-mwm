<?php
/**
 * Milkyway Multimedia
 * User.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shortcodes;

class GoogleFixUrl implements Contract {
	public function isAvailableForUse($member = null) {
		return true;
	}

	public function render($arguments, $caption = null, $parser = null)
	{
		return \ArrayData::create($arguments)->renderWith('GoogleFixURL');
	}

	public function code()
	{
		return 'google_fixurl';
	}

	public function title()
	{
		return [
			'google_fixurl' => _t('Shortcodable.GOOGLE_FIX_URL', 'Google search site plugin'),
		];
	}

	public function formField()
	{
		return \CompositeField::create();
	}
} 