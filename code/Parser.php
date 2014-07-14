<?php namespace Milkyway;
/**
 * Milkyway Multimedia
 * Parser.php
 *
 * @package milkyway-multimedia/silverstripe-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class Parser extends \TextParser {
    public function parse() {
        return \ShortcodeParser::get_active()->parse($this->content);
    }
}