<?php

Requirements::set_backend(new \Milkyway\Assets_Backend());

if (ClassInfo::exists('SiteTree'))
{
    ShortcodeParser::get('default')->register('site_config', array('Milkyway\Shortcodes', 'site_config_parser'));
}

ShortcodeParser::get('default')->register('user', array('Milkyway\Shortcodes', 'current_member_parser'));
ShortcodeParser::get('default')->register('google_fixurl', array('Milkyway\Shortcodes', 'google_fixurl_parser'));
ShortcodeParser::get('default')->register('current_page', array('Milkyway\Shortcodes', 'current_page_parser'));
ShortcodeParser::get('default')->register('icon', array('Milkyway\Shortcodes', 'css_icon_parser'));