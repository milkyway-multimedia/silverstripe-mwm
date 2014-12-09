<?php
/**
 * Milkyway Multimedia
 * Contract.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shortcodes;


interface Contract {
	public function isAvailableForUse($member = null);

	public function render($arguments, $caption = null, $parser = null);

	public function code();

	public function title();

	public function formField();
}