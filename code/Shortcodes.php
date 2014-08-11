<?php namespace Milkyway\SS;
/**
 * Milkyway Multimedia
 * Shortcodes.php
 *
 * @package milkyway-multimedia/silverstripe-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class Shortcodes {
    public static $google_fixurl_js = 'http://linkhelp.clients.google.com/tbproxy/lh/wm/fixurl.js';

    // A short code parser for the site_config
    public static function site_config_parser($arguments, $caption = null, $parser = null) {
        if(!array_key_exists('field', $arguments) || !$arguments['field']) return '';

        $field = $arguments['field'];
        $siteConfig = \SiteConfig::current_site_config();

        if(!$siteConfig->hasField($field)) return '';

        $value = $siteConfig->obj($field);

        if($value instanceof DBField) {
            if(isset($arguments['type']) && $value->hasMethod($arguments['type']))
                $cast = $arguments['type'];
            else
                $cast = 'Nice';

            $value = $value->$cast();
        }

        if($parser) {
            $caption = $parser->parse($caption);
            $value = $parser->parse($value);
        }

        if(isset($arguments['caption']))
            $caption = $arguments['caption'];

        if(!$caption)
            $caption = $value;

        if(\Email::validEmailAddress($value) && !isset($arguments['nolink']))
            return '<a href="mailto:' . $value . '">' . $caption . '</a>';

        if(filter_var($value, FILTER_VALIDATE_URL) && !isset($arguments['nolink']))
            return '<a href="' . $value . '">' . $caption . '</a>';

        return $value;
    }

    // A short code parser for the current_member
    public static function current_member_parser($arguments, $caption = null, $parser = null) {
        if(!array_key_exists('field', $arguments) || !$arguments['field']) return '';

        $field = $arguments['field'];
        $member = \Member::currentUser();

        if(!$member) return '';
        if(!$member->hasField($field)) return '';

        $value = $member->obj($field);

        if($value instanceof DBField) {
            if(isset($arguments['type']) && $value->hasMethod($arguments['type']))
                $cast = $arguments['type'];
            else
                $cast = 'Nice';

            $value = $value->$cast();
        }

        if($parser) {
            $caption = $parser->parse($caption);
            $value = $parser->parse($value);
        }

        if(isset($arguments['caption']))
            $caption = $arguments['caption'];

        if(!$caption)
            $caption = $value;

        if(\Email::is_valid_address($value) && !isset($arguments['nolink']))
            return '<a href="mailto:' . $value . '">' . $caption . '</a>';

        if(filter_var($value, FILTER_VALIDATE_URL) && !isset($arguments['nolink']))
            return '<a href="' . $value . '">' . $caption . '</a>';

        return $value;
    }

    // A short code parser for the current page
    public static function current_page_parser($arguments, $caption = null, $parser = null) {
        if(!array_key_exists('field', $arguments) || !$arguments['field']) return '';

        $field = $arguments['field'];
        $curr = \Controller::curr();

        if($curr->hasField($field) || $curr->hasMethod($field))
            $value = $curr->hasMethod($field) ? $curr->$field() : $curr->obj($field);
        else {
            if($curr && !$curr->hasMethod('data')) return '';
            $page = $curr->data();

            if(!$page) return '';

            if(!$page->hasField($field) && !$page->hasMethod($field)) return;

            if(array_key_exists('type', $arguments))
                $type = $arguments['type'];
            else
                $type = 'Nice';

            $value = $page->hasMethod($field) ? $page->$field() : $page->obj($field)->$type();
        }

        return $value;
    }

    // A google fix url parser
    public static function google_fixurl_parser($arguments, $content = null, $parser = null) {
        return \ArrayData::create($arguments)->renderWith('GoogleFixURL');
    }

    // A short code parser for css icons
    public static function css_icon_parser($arguments, $content = null, $parser = null) {
        if(isset($arguments['use']))
            $content = $arguments['use'];

        if(!$content) return '';

        if(isset($arguments['prepend']) && $arguments['prepend'])
            $prepend = $arguments['prepend'];
        else
            $prepend = 'icon icon-';

        if(isset($arguments['classes']) && $arguments['classes'])
            $content .= ' ' . $arguments['classes'];

        return '<i class="' .  $prepend . $content . '"></i>';
    }
} 