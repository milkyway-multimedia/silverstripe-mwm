<?php namespace Milkyway\SS\Core\Extensions;

/**
 * Milkyway Multimedia
 * Member.php
 *
 * @package milkyway-multimedia/ss-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use DataExtension;

use FieldList;
use PermissionCheckboxSetField;

class Group extends DataExtension {
    public function updateCMSFields(FieldList $fields)
    {
        if($currentPermsField = $fields->dataFieldByName('Permissions')) {
            $fields->replaceField('Permissions', $permsField = PermissionCheckboxSetField::create(
                $currentPermsField->Name,
                $currentPermsField->Title(),
                'Permission',
                'GroupID',
                $this->owner
            ));

            $permsField->setHiddenPermissions($currentPermsField->HiddenPermissions);
        }
    }
} 