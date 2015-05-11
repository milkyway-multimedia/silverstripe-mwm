<?php namespace Milkyway\SS\Extensions;
use Milkyway\SS\Utilities;

/**
 * Milkyway Multimedia
 * DataObject.php
 *
 * @package milkyway-multimedia/ss-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class DataObject extends \DataExtension {
    function i18n_description() {
        return _t(get_class($this->owner).'.DESCRIPTION', $this->owner->config()->description);
    }

    public function firstOrMake($filter = [], $additionalData = [], $write = true) {
        if (!($record = $this->owner->get()->filter($filter)->first())) {
            $record = $this->owner->create(array_merge($filter, $additionalData));

            if($write) {
                $record->write();
                $record->isNew = true;
            }
        }

        return $record;
    }

    public function firstOrCreate($filter = [], $additionalData = [], $write = true) {
        return $this->owner->firstOrMake($filter, $additionalData, $write);
    }

    public function is_a($class) {
        return Utilities::is_instanceof($class, $this->owner);
    }

    public function is_not_a($class) {
        return !Utilities::is_instanceof($class, $this->owner);
    }
} 