<?php

if (!defined('SS_MWM_DIR'))
	define('SS_MWM_DIR', basename(rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR)));

Requirements::set_backend(new \Milkyway\SS\Assets_Backend());

if (ClassInfo::exists('SiteConfig')) {
	ShortcodeParser::get('default')->register('site_config', ['Milkyway\SS\Shortcodes', 'site_config_parser']);
}

ShortcodeParser::get('default')->register('user', ['Milkyway\SS\Shortcodes', 'current_member_parser']);
ShortcodeParser::get('default')->register('google_fixurl', ['Milkyway\SS\Shortcodes', 'google_fixurl_parser']);
ShortcodeParser::get('default')->register('current_page', ['Milkyway\SS\Shortcodes', 'current_page_parser']);
ShortcodeParser::get('default')->register('icon', ['Milkyway\SS\Shortcodes', 'css_icon_parser']);

if (!function_exists('with')) {
	function with($object)
	{
		return $object;
	}
}