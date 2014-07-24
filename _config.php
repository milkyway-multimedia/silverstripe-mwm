<?php

Requirements::set_backend(new \Milkyway\SS\Assets_Backend());

if (ClassInfo::exists('SiteTree')) {
    ShortcodeParser::get('default')->register('site_config', array('Milkyway\SS\Shortcodes', 'site_config_parser'));
}

ShortcodeParser::get('default')->register('user', array('Milkyway\SS\Shortcodes', 'current_member_parser'));
ShortcodeParser::get('default')->register('google_fixurl', array('Milkyway\SS\Shortcodes', 'google_fixurl_parser'));
ShortcodeParser::get('default')->register('current_page', array('Milkyway\SS\Shortcodes', 'current_page_parser'));
ShortcodeParser::get('default')->register('icon', array('Milkyway\SS\Shortcodes', 'css_icon_parser'));