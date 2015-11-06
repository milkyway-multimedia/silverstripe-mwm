<?php namespace Milkyway\SS\Core\Overrides;

/**
 * Milkyway Multimedia
 * UploadField_SelectHandler.php
 *
 * @package milkyway-multimedia/ss-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use PermissionCheckboxSetField as Original;
use Permission;

use ArrayList;
use DataObjectInterface;
use DBField;

class PermissionCheckboxSetField extends Original
{
    protected static $_valuesFromRecord = [];

    public function __construct($name, $title, $managedClass, $filterField, $records)
    {
        parent::__construct($name, $title, $managedClass, $filterField, $records);

        if($this->readonly) {
            $this->addExtraClass(strtolower(get_parent_class()) . '_readonly');
        }
        else {
            $this->addExtraClass(strtolower(get_parent_class()));
        }
    }

    public function performReadonlyTransformation() {
        $readonly = PermissionCheckboxSetField_Readonly::create(
            $this->name,
            $this->title,
            $this->managedClass,
            $this->filterField,
            $this->records
        );

        return $readonly;
    }

    public function Field($properties = [])
    {
        singleton('require')->css(FRAMEWORK_DIR . '/css/CheckboxSetField.css');
        singleton('require')->javascript(FRAMEWORK_DIR . '/javascript/PermissionCheckboxSetField.js');

        if(isset(static::$_valuesFromRecord['uninheritedCodes'])) {
            $uninheritedCodes = static::$_valuesFromRecord['uninheritedCodes'];
        }

        if(isset(static::$_valuesFromRecord['inheritedCodes'])) {
            $inheritedCodes = static::$_valuesFromRecord['inheritedCodes'];
        }

        $odd = 0;
        $options = '';
        $globalHidden = (array)Permission::config()->hidden_permissions;
        if ($this->source) {
            $privilegedPermissions = Permission::config()->privileged_permissions;

            // loop through all available categorized permissions and see if they're assigned for the given groups
            foreach ($this->source as $categoryName => $permissions) {
                $options .= "<li><h5>$categoryName</h5></li>";
                foreach ($permissions as $code => $permission) {
                    if (in_array($code, $this->hiddenPermissions)) {
                        continue;
                    }
                    if (in_array($code, $globalHidden)) {
                        continue;
                    }

                    $value = $permission['name'];

                    $odd = ($odd + 1) % 2;
                    $extraClass = $odd ? 'odd' : 'even';
                    $extraClass .= ' val' . str_replace(' ', '', $code);
                    $itemID = $this->id() . '_' . preg_replace('/[^a-zA-Z0-9]+/', '', $code);
                    $checked = $disabled = $inheritMessage = '';
                    $checked = (isset($uninheritedCodes[$code]) || isset($inheritedCodes[$code]))
                        ? ' checked="checked"'
                        : '';
                    $title = $permission['help']
                        ? 'title="' . htmlentities($permission['help'], ENT_COMPAT, 'UTF-8') . '" '
                        : '';

                    if (isset($inheritedCodes[$code])) {
                        // disable inherited codes, as any saving logic would be too complicate to express in this
                        // interface
                        $disabled = ' disabled="true"';
                        $inheritMessage = ' (' . join(', ', $inheritedCodes[$code]) . ')';
                    } elseif ($this->records && $this->records->Count() > 1 && isset($uninheritedCodes[$code])) {
                        // If code assignments are collected from more than one "source group",
                        // show its origin automatically
                        $inheritMessage = ' (' . join(', ', $uninheritedCodes[$code]) . ')';
                    }

                    // Disallow modification of "privileged" permissions unless currently logged-in user is an admin
                    if (!Permission::check('ADMIN') && in_array($code, $privilegedPermissions)) {
                        $disabled = ' disabled="true"';
                    }

                    // If the field is readonly, always mark as "disabled"
                    if ($this->readonly) {
                        $disabled = ' disabled="true"';
                    }

                    $inheritMessage = '<small>' . $inheritMessage . '</small>';
                    $icon = ($checked) ? 'accept' : 'decline';

                    // If the field is readonly, add a span that will replace the disabled checkbox input
                    if ($this->readonly) {
                        $options .= "<li class=\"$extraClass\">"
                            . "<input id=\"$itemID\"$disabled name=\"$this->name[$code]\" type=\"checkbox\""
                            . " value=\"$code\"$checked class=\"checkbox\" />"
                            . "<label {$title}for=\"$itemID\">"
                            . "<span class=\"ui-button-icon-primary ui-icon btn-icon-$icon\"></span>"
                            . "$value$inheritMessage</label>"
                            . "</li>\n";
                    } else {
                        $options .= "<li class=\"$extraClass\">"
                            . "<input id=\"$itemID\"$disabled name=\"$this->name[$code]\" type=\"checkbox\""
                            . " value=\"$code\"$checked class=\"checkbox\" />"
                            . "<label {$title}for=\"$itemID\">$value$inheritMessage</label>"
                            . "</li>\n";
                    }
                }
            }
        }

        static::$_valuesFromRecord = [];

        if ($this->readonly) {
            return DBField::create_field('HTMLText',
                "<ul id=\"{$this->id()}\" class=\"optionset checkboxsetfield{$this->extraClass()}\">\n" .
                "<li class=\"help\">" .
                _t(
                    'Permissions.UserPermissionsIntro',
                    'Assigning groups to this user will adjust the permissions they have.'
                    . ' See the groups section for details of permissions on individual groups.'
                ) .
                "</li>" .
                $options .
                "</ul>\n"
            );
        } else {
            return DBField::create_field('HTMLText',
                "<ul id=\"{$this->id()}\" class=\"optionset checkboxsetfield{$this->extraClass()}\">\n" .
                $options .
                "</ul>\n"
            );
        }
    }

    public function setValue($value = null)
    {
        if(!$value) {
            $this->useCodesFromRecords();
        }

        return parent::setValue($value);
    }

    protected function useCodesFromRecords() {
        $uninheritedCodes = [];
        $inheritedCodes = [];
        $records = ($this->records) ? $this->records : ArrayList::create();

        // Get existing values from the form record (assuming the formfield name is a join field on the record)
        if (is_object($this->form)) {
            $record = $this->form->getRecord();
            if (
                $record
                && (is_a($record, 'Group') || is_a($record, 'PermissionRole'))
                && !$records->find('ID', $record->ID)
            ) {
                $records->push($record);
            }
        }

        // Get all 'inherited' codes not directly assigned to the group (which is stored in $values)
        foreach ($records as $record) {
            // Get all uninherited permissions
            $relationMethod = $this->name;
            foreach ($record->$relationMethod() as $permission) {
                if (!isset($uninheritedCodes[$permission->Code])) {
                    $uninheritedCodes[$permission->Code] = [];
                }
                $uninheritedCodes[$permission->Code][] = _t(
                    'PermissionCheckboxSetField.AssignedTo', 'assigned to "{title}"',
                    ['title' => $record->dbObject('Title')->forTemplate()]
                );
            }

            // Special case for Group records (not PermissionRole):
            // Determine inherited assignments
            if (is_a($record, 'Group')) {
                // Get all permissions from roles
                if ($record->Roles()->Count()) {
                    foreach ($record->Roles() as $role) {
                        foreach ($role->Codes() as $code) {
                            if (!isset($inheritedCodes[$code->Code])) {
                                $inheritedCodes[$code->Code] = [];
                            }
                            $inheritedCodes[$code->Code][] = _t(
                                'PermissionCheckboxSetField.FromRole',
                                'inherited from role "{title}"',
                                'A permission inherited from a certain permission role',
                                ['title' => $role->dbObject('Title')->forTemplate()]
                            );
                        }
                    }
                }

                // Get from parent groups
                $parentGroups = $record->getAncestors();
                if ($parentGroups) {
                    foreach ($parentGroups as $parent) {
                        if (!$parent->Roles()->Count()) {
                            continue;
                        }
                        foreach ($parent->Roles() as $role) {
                            if ($role->Codes()) {
                                foreach ($role->Codes() as $code) {
                                    if (!isset($inheritedCodes[$code->Code])) {
                                        $inheritedCodes[$code->Code] = [];
                                    }
                                    $inheritedCodes[$code->Code][] = _t(
                                        'PermissionCheckboxSetField.FromRoleOnGroup',
                                        'inherited from role "%s" on group "%s"',
                                        'A permission inherited from a role on a certain group',
                                        [
                                            'roletitle'  => $role->dbObject('Title')->forTemplate(),
                                            'grouptitle' => $parent->dbObject('Title')->forTemplate(),
                                        ]
                                    );
                                }
                            }
                        }
                        if ($parent->Permissions()->Count()) {
                            foreach ($parent->Permissions() as $permission) {
                                if (!isset($inheritedCodes[$permission->Code])) {
                                    $inheritedCodes[$permission->Code] = [];
                                }
                                $inheritedCodes[$permission->Code][] =
                                    _t(
                                        'PermissionCheckboxSetField.FromGroup',
                                        'inherited from group "{title}"',
                                        'A permission inherited from a certain group',
                                        ['title' => $parent->dbObject('Title')->forTemplate()]
                                    );
                            }
                        }
                    }
                }
            }
        }

        static::$_valuesFromRecord = [
            'inheritedCodes' => $inheritedCodes,
            'uninheritedCodes' => $uninheritedCodes,
            'records' => $records,
        ];
    }

    public function Value() {
        return $this->value;
    }
}

class PermissionCheckboxSetField_Readonly extends PermissionCheckboxSetField {

    protected $readonly = true;

    public function saveInto(DataObjectInterface $record) {
        return false;
    }
}