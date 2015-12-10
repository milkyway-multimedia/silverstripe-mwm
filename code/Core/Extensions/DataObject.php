<?php namespace Milkyway\SS\Core\Extensions;

/**
 * Milkyway Multimedia
 * DataObject.php
 *
 * @package milkyway-multimedia/ss-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use DataExtension;
use DataObjectInterface;

class DataObject extends DataExtension
{
    private $inheritedObjCache = [];

    function i18n_description()
    {
        return _t(get_class($this->owner) . '.DESCRIPTION', $this->owner->config()->description);
    }

    public function firstOrMake($filter = [], $additionalData = [], $write = true)
    {
        if (!($record = $this->owner->get()->filter($filter)->first())) {
            $record = $this->owner->create(array_merge($filter, $additionalData));

            if ($write) {
                $record->write();
                $record->isNew = true;
            }
        }

        return $record;
    }

    public function firstOrCreate($filter = [], $additionalData = [], $write = true)
    {
        return $this->owner->firstOrMake($filter, $additionalData, $write);
    }

    public function is_a($class)
    {
        return singleton('mwm')->is_instanceof($class, $this->owner);
    }

    public function is_not_a($class)
    {
        return !$this->is_a($class);
    }

    // Get an inherited object (search parents and home page for the method/value)
    public function InheritedObj($fieldName, $arguments = null, $cache = true, $cacheName = null, $includePrefix = '')
    {
        $value = null;
        $firstVal = null;

        if (!$cacheName) {
            $cacheName = $arguments ? $fieldName . implode(',', $arguments) : $fieldName;
        }

        $cacheName = get_class($this->owner) . '__' . $cacheName;

        if (!isset($this->inheritedObjCache[$cacheName]) || !$cache) {
            $keyParts = explode('.', $fieldName);
            $fieldName = array_shift($keyParts);

            $value = $this->owner->obj($fieldName, $arguments, $cache, $cacheName);

            if (!empty($keyParts) && $value instanceof DataObjectInterface) {
                foreach ($keyParts as $keyPart) {
                    $value = $value->obj($keyPart, $arguments, $cache, $cacheName);
                }
            }

            if ($value && $value->hasMethod('exists') && !$value->exists()) {
                if ($firstVal === null) {
                    $firstVal = $value;
                }
                $value = null;
            }

            if (!$value) {
                $page = $this->owner;

                while ($page != null && $page->ID) {
                    $value = $page->obj($fieldName, $arguments, $cache, $cacheName);

                    if (!empty($keyParts) && $value instanceof DataObjectInterface) {
                        foreach ($keyParts as $keyPart) {
                            $value = $value->obj($keyPart, $arguments, $cache, $cacheName);
                        }
                    }

                    if ($value && $value->hasMethod('exists') && !$value->exists()) {
                        if ($firstVal === null) {
                            $firstVal = $value;
                        }
                        $value = null;
                    } else {
                        if ($value) {
                            $value->__inheritedFrom = $page;
                        }
                    }

                    if ($value) {
                        break;
                    }

                    if($this->owner->hasMethod('Parent')) {
                        $page = $page->Parent();
                    }
                    else {
                        $page = null;
                    }
                }
            }

            if (!$value && ($home = singleton('director')->homePage()) && $home !== $this->owner) {
                $value = $home->obj($fieldName, $arguments, $cache, $cacheName);

                if (!empty($keyParts) && $value instanceof DataObjectInterface) {
                    foreach ($keyParts as $keyPart) {
                        $value = $value->obj($keyPart, $arguments, $cache, $cacheName);
                    }
                }
            }

            if ($value && $value->hasMethod('exists') && !$value->exists()) {
                if ($firstVal === null) {
                    $firstVal = $value;
                }
                $value = null;
            } else {
                if ($value && isset($home)) {
                    $value->__inheritedFrom = $home;
                }
            }

            if (!$value && class_exists('SiteConfig') && ($siteConfig = \SiteConfig::current_site_config()) && $siteConfig !== $this) {
                $value = $siteConfig->obj($includePrefix . $fieldName, $arguments, $cache, $cacheName);

                if (!empty($keyParts) && $value instanceof DataObjectInterface) {
                    foreach ($keyParts as $keyPart) {
                        $value = $value->obj($keyPart, $arguments, $cache, $cacheName);
                    }
                }
            }

            if ($value && $value->hasMethod('exists') && !$value->exists()) {
                if ($firstVal === null) {
                    $firstVal = $value;
                }
                $value = null;
            } else {
                if ($value && isset($siteConfig)) {
                    $value->__inheritedFrom = $siteConfig;
                }
            }

            if ($value === null) {
                $value = $firstVal;
            }

            if ($cache) {
                $this->inheritedObjCache[$cacheName] = $value;
            }
        } else {
            $value = $this->inheritedObjCache[$cacheName];
        }

        return $value;
    }
} 