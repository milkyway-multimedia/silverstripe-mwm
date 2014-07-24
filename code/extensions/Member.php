<?php namespace Milkyway\SS\Extensions;
/**
 * Milkyway Multimedia
 * Member.php
 *
 * @package milkyway-multimedia/silverstripe-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class Member extends \DataExtension {
    protected static $_cache_access_cms = [];

    function canAccessCMS($member = null) {
        if(!$member) $member = $this->owner;
        if(is_object($member)) $member = $member->ID;

        if(isset(self::$_cache_access_cms[$member->ID]))
            return self::$_cache_access_cms[$member->ID];

        $members = \Member::mapInCMSGroups();

        if($members && $members->count())
            $result = $members->offsetExists($member);
        else
            $result = false;

        self::$_cache_access_cms[$member->ID] = $result;

        return $result;
    }
} 