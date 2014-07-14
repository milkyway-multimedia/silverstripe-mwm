<?php namespace Milkyway\Extensions;
/**
 * Milkyway Multimedia
 * DataObject.php
 *
 * @package milkyway-multimedia/silverstripe-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class DataObject extends \DataExtension {
    function i18n_description() {
        return _t(get_class($this->owner).'.DESCRIPTION', $this->owner->config()->description);
    }
} 