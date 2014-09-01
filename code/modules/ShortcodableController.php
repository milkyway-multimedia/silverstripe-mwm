<?php namespace Milkyway\SS\Modules;

use Milkyway\SS\Utilities;

/**
 * Milkyway Multimedia
 * ShortcodeForm.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class ShortcodableController extends \Extension
{
    public function updateShortcodeForm($form)
    {
        singleton('ShortcodableParser')->register('user');
        singleton('ShortcodableParser')->register('current_page');
        singleton('ShortcodableParser')->register('icon');
        singleton('ShortcodableParser')->register('google_fixurl');

        if (\ClassInfo::exists('SiteConfig')) {
            singleton('ShortcodableParser')->register('site_config');
        }

        $classname     = false;
        $shortcodeData = false;

        if ($shortcode = $this->owner->Request->requestVar('Shortcode')) {
            $shortcodeData = singleton('ShortcodableParser')->the_shortcodes([], $shortcode);
            if (isset($shortcodeData[0])) {
                $shortcodeData = $shortcodeData[0];
                $classname     = $shortcodeData['name'];
            }
        } else {
            $classname = $this->request->requestVar('ShortcodeType');
        }

        if ($types = $form->Fields()->fieldByName('ShortcodeType')) {
            $types->setTitle(_t('Shortcodable.SHORTCODE_TYPE', 'Shortcode type'));

            $source = $types->Source;

            if (\ClassInfo::exists('SiteConfig')) {
                $source['site_config'] = _t('Shortcodable.SITE_SETTING', 'Site setting');
            }

            $source = array_merge(
                $source,
                [
                    'user'          => _t('Shortcodable.LOGGED_IN_MEMBER_SETTING', 'Logged-in member'),
                    'current_page'  => _t('Shortcodable.CURRENT_PAGE', 'Current page'),
                    'icon'          => _t('Shortcodable.ICON', 'Icon'),
                    'google_fixurl' => _t('Shortcodable.GOOGLE_FIX_URL', 'Google search site plugin'),
                ]
            );

            $types->setSource($source);

            if ($classname) {
                $types->setValue($classname);
            }

            if ($types->Value()) {
                switch ($types->Value()) {
                    case 'user':
                        $shortcodes = Utilities::map_array_to_i18n(\Member::config()->valid_shortcode_fields, 'Member');
                        natsort($shortcodes);

                        $form->Fields()->push(
                            \CompositeField::create(
                                \FieldList::create(
                                    \DropdownField::create(
                                        'field',
                                        _t('Shortcodable.FIELD', 'Field'),
                                        $shortcodes
                                    )->setForm($form),
                                    \DropdownField::create(
                                        'type',
                                        _t('Shortcodable.DISPLAY_TYPE', 'Display type'),
                                        [
                                            '' => 'Nice',
                                        ]
                                    )->setForm($form),
                                    \TextField::create(
                                        'caption',
                                        _t('Shortcodable.CAPTION', 'Caption')
                                    )->setDescription(
                                        _t(
                                            'Shortcodable.DESC-CAPTION',
                                            'Only used for values that will resolve to links'
                                        )
                                    )->setForm($form),
                                    \DropdownField::create(
                                        'nolink',
                                        _t('Shortcodable.NO_AUTO_LINK', 'Autolink'),
                                        [
                                            ''  => 'Yes',
                                            '1' => 'No',
                                        ]
                                    )->setForm($form)
                                )
                            )->addExtraClass('attributes-composite')
                        );
                        break;
                    case 'site_config':
                        $shortcodes = Utilities::map_array_to_i18n(
                            \SiteConfig::config()->valid_shortcode_fields,
                            'SiteConfig'
                        );
                        natsort($shortcodes);

                        $form->Fields()->push(
                            \CompositeField::create(
                                \FieldList::create(
                                    \DropdownField::create(
                                        'field',
                                        _t('Shortcodable.FIELD', 'Field'),
                                        $shortcodes
                                    )->setForm($form),
                                    \DropdownField::create(
                                        'type',
                                        _t('Shortcodable.DISPLAY_TYPE', 'Display type'),
                                        [
                                            '' => 'Nice',
                                        ]
                                    )->setForm($form),
                                    \TextField::create(
                                        'caption',
                                        _t('Shortcodable.CAPTION', 'Caption')
                                    )->setDescription(
                                        _t(
                                            'Shortcodable.DESC-CAPTION',
                                            'Only used for values that will resolve to links'
                                        )
                                    )->setForm($form),
                                    \DropdownField::create(
                                        'nolink',
                                        _t('Shortcodable.NO_AUTO_LINK', 'Autolink'),
                                        [
                                            ''  => 'Yes',
                                            '1' => 'No',
                                        ]
                                    )->setForm($form)
                                )
                            )->addExtraClass('attributes-composite')
                        );
                        break;
                    case 'current_page':
                        $form->Fields()->push(
                            \CompositeField::create(
                                \FieldList::create(
                                    \FormMessageField::create(
                                        'NOTE-ADVANCED',
                                        _t(
                                            'Shortcodable.NOTE-ADVANCED',
                                            'Note: This is an advanced shortcode. It is recommended to use only use the suggested codes.'
                                        )
                                    )->cms()->setForm($form),
                                    \TextField::create(
                                        'field',
                                        _t('Shortcodable.FIELD', 'Field')
                                    )->setDescription(
                                        _t(
                                            'Shortcodable.DESC-CurrentPageField',
                                            'Suggested codes: Title, Subtitle, Content, Link, AbsoluteLink'
                                        )
                                    )->setForm($form),
                                    \DropdownField::create(
                                        'type',
                                        _t('Shortcodable.DISPLAY_TYPE', 'Display type'),
                                        [
                                            '' => 'Nice',
                                        ]
                                    )->setForm($form)
                                )
                            )->addExtraClass('attributes-composite')
                        );
                        break;
                    case 'icon':
                        if (\HTMLEditorField::config()->valid_icon_shortcodes) {
                            $icon = \DropdownField::create(
                                'use',
                                _t('Shortcodable.ICON', 'Icon'),
                                Utilities::map_array_to_i18n(\HTMLEditorField::config()->valid_icon_shortcodes, 'Icon')
                            );
                        } else {
                            $icon = \TextField::create('use', _t('Shortcodable.ICON', 'Icon'));
                        }

                        if (\HTMLEditorField::config()->prepend_icon) {
                            $iconPrepend = \ReadonlyField::create(
                                'prepend',
                                _t('Shortcodable.ICON_PREPEND', 'Prepend'),
                                \HTMLEditorField::config()->prepend_icon
                            );
                        } else {
                            $iconPrepend = \TextField::create(
                                'prepend',
                                _t('Shortcodable.ICON_PREPEND', 'Prepend')
                            )->setAttribute('placeholder', 'icon icon-');
                        }

                        if (\HTMLEditorField::config()->valid_icon_classes) {
                            $iconClasses = \DropdownField::create(
                                'classes',
                                _t('Shortcodable.ICON_CLASSES', 'Classes'),
                                array_combine(
                                    \HTMLEditorField::config()->valid_icon_classes,
                                    \HTMLEditorField::config()->valid_icon_classes
                                )
                            );
                        } else {
                            $iconClasses = \TextField::create('classes', _t('Shortcodable.ICON_CLASSES', 'Classes'));
                        }

                        $form->Fields()->push(
                            \CompositeField::create(
                                \FieldList::create(
                                    $icon,
                                    $iconPrepend,
                                    $iconClasses
                                )
                            )->addExtraClass('attributes-composite')
                        );
                        break;
                }
            }
        }

        if ($shortcodeData && isset($shortcodeData['atts'])) {
            $form->loadDataFrom($shortcodeData['atts']);
        }
    }
} 