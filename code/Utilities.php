<?php namespace Milkyway\SS;

/**
 * Milkyway Multimedia
 * Utilities.php
 *
 * @package milkyway-multimedia/silverstripe-mwm
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class Utilities implements \TemplateGlobalProvider
{
	// Set cookie, to use the same cookie domain as Session
	public static function set_cookie($name, $value, $expiry = 90, $path = null, $domain = null, $secure = false, $httpOnly = false)
	{
		$domain = ($domain) ? $domain : \Config::inst()->get('Cookie', 'cookie_domain') ?: \Config::inst()->get('Session', 'cookie_domain');
		\Cookie::set($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
	}

	public static function force_cookie_expiry($name, $path = null, $domain = null)
	{
		$domain = ($domain) ? $domain : \Config::inst()->get('Cookie', 'cookie_domain') ?: \Config::inst()->get('Session', 'cookie_domain');
		\Cookie::force_expiry($name, $path, $domain);
	}

	// Replace fields in content with record fields
	public static function parse_record_fields($content, $data = [], $record = null, $ignore = [], $html = true)
	{
		$content = new \SSViewer_FromString($content);

		if (!$record)
			$record = \ArrayData::create($data);
		else
			$record->customise($data);

		if (count($ignore)) {
			foreach ($ignore as $i)
				$record->$i = null;
		}

		if ($html)
			return \DBField::create_field('HTMLText', $content->process($record))->forTemplate();
		else
			return \DBField::create_field('Text', $content->process($record))->forTemplate();
	}

	// A workaround the form system to save the relation fields found in the record
	// into the proper relation (since it is not being read as an array, it is harder
	// than it should be)
	public static function save_from_map(\DataObject $record, $relation, $regField = '')
	{
		$map = $record->toMap();
		$rel = $record->getComponent($relation);
		$changed = false;
		if (!$regField) $regField = $relation;

		foreach ($map as $field => $val) {
			if (preg_match('/^' . preg_quote($regField) . '\[(\w+)\]$/', $field, $match) !== false) {
				if (count($match) == 2 && isset($match[1])) {
					$rel->setCastedField($match[1], $val);
					$changed = true;
				}
			}
		}

		if ($changed) {
			$rel->write();

			return true;
		}

		return false;
	}

	public static function get_opengraph_metatag($property = 'type', $value = 'website', $namespace = 'og')
	{
		if ($namespace)
			return sprintf('<meta property="%s:%s" content="%s" />', $namespace, $property, $value);
		else
			return sprintf('<meta property="%s" content="%s" />', $property, $value);
	}

	public static function adminEmail($prepend = '')
	{
		if ($email = \Config::inst()->get('Email', 'admin_email'))
			return $prepend ? $prepend . '+' . $email : $email;

		$name = $prepend ? $prepend . '+no-reply' : 'no-reply';

		return $name . '@' . Director::baseWebsiteURL();
	}

	private static $_visitorCountry;

	public static function get_visitor_country()
	{
		if (!self::$_visitorCountry) {
			$ip = '';

			if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
				//check ip from share internet
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				//to check ip is pass from proxy
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} elseif (isset($_SERVER['REMOTE_ADDR'])) {
				$ip = $_SERVER['REMOTE_ADDR'];
			}

			if (!$ip)
				self::$_visitorCountry = (object)['Code' => 'AU', 'Name' => 'Australia'];
			elseif ($country = \Session::get('Milkyway.UserInfo.Country'))
				self::$_visitorCountry = $country;
			else {
				$ipData = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));

				if ($ipData && $ipData->geoplugin_countryName != null)
					self::$_visitorCountry = (object)['Code' => $ipData->geoplugin_countryCode, 'Name' => $ipData->geoplugin_countryName];
				else
					self::$_visitorCountry = (object)['Code' => 'AU', 'Name' => 'Australia'];

				\Session::set('Milkyway.UserInfo.Country', self::$_visitorCountry);
			}
		}

		return self::$_visitorCountry;
	}

	private static $_countryList;

	public static function get_country_list()
	{
		if (!self::$_countryList) {
			self::$_countryList = \Zend_Locale::getTranslationList('territory', \i18n::get_locale(), 2);
			if (isset(self::$_countryList['ZZ'])) unset(self::$_countryList['ZZ']);
			asort(self::$_countryList);
		}

		return self::$_countryList;
	}

	public static function get_full_country_name($code)
	{
		$locale = \Controller::curr()->Locale;
		if (!$locale)
			$locale = \i18n::get_locale();

		if ($country = \Zend_Locale::getTranslation($code, 'territory', $locale))
			return $country;

		return $code;
	}

	private static $_monthList;

	public static function get_month_list($type = 'wide')
	{
		if (!self::$_monthList)
			self::$_monthList = \Zend_Locale::getTranslationList('months', \i18n::get_locale());

		if (isset(self::$_monthList['format'][$type]))
			$months = self::$_monthList['format'][$type];
		else
			$months = self::$_monthList['format'][self::$_monthList['default']];

		return $months;
	}

	public static function get_template_global_variables()
	{
		return [
			'contentLocale',
			'localeLanguage',
			'canAccessCMS',
			'canEditCurrentPage',
			'inlineFile',
			'placeIMG',
			'appDir',
			'is' => 'is_instanceof',
			'isFrontendEditingEnabled',
		];
	}

	public static function inlineFile($files, $theme = false)
	{
		if ($theme) {
			$theme = ($theme === true || $theme == 1) ? \Config::inst()->get('SSViewer', 'theme') : $theme;

			if ($theme)
				$theme = THEMES_DIR . '/' . $theme;
			else
				$theme = project();
		}

		$files = explode(',', $files);

		foreach($files as $file) {
			$file = $theme ? $theme . '/' . $file : $file;
			$file = \Director::is_absolute_url($file) ? $file : \Director::getAbsFile($file);

			if (\Director::is_absolute_url($file) || @file_exists($file))
				return \DBField::create_field('HTMLText', @file_get_contents($file));
		}

		return '';
	}

	public static function canAccessCMS()
	{
		return ($member = \Member::currentUser()) ? $member->canAccessCMS() : false;
	}

	public static function canEditCurrentPage()
	{
		return ($member = \Member::currentUser()) ? $member->canEdit() : false;
	}

	public static function appDir() {
		return project();
	}

	public static function contentLocale($noTransform = 0)
	{
		$locale = \Controller::curr() && \Controller::curr()->Locale ? \Controller::curr()->Locale : \i18n::get_locale();

		if ($noTransform)
			return $locale;

		return \i18n::convert_rfc1766($locale);
	}

	public static function localeLanguage()
	{
		return \i18n::get_lang_from_locale(self::contentLocale(1));
	}

	public static function placeIMG($width = 400, $height = 300, $categories = 'any', $filters = '')
	{
		return \Controller::join_links(\Director::protocol() . 'placeimg.com', $width, $height, $categories, $filters);
	}

	public static function loremIpsum($paragraphs = 1, $length = 'short', $opts = ['plaintext'])
	{
		$key = preg_replace('/[^a-zA-Z0-9_]/', '', $paragraphs . '_' . $length . '_' . implode('_', $opts));

		if(!($text = self::loremIpsumCache()->load($key))) {
			$text = @file_get_contents(\Controller::join_links('http://loripsum.net/api', $paragraphs, $length, implode('/', $opts)));
			self::loremIpsumCache()->save($text, $key);
		}

		return $text;
	}

	protected static $template_cache;

	protected static function loremIpsumCache() {
		if(!self::$template_cache)
			self::$template_cache = \SS_Cache::factory('LoremIpsum', 'Output', ['lifetime' => 100000000]);

		return self::$template_cache;
	}

	/**
	 * Convert a value to be suitable for an HTML ID attribute. Replaces non
	 * supported characters with an underscore.
	 *
	 * @temporary
	 *
	 * @see http://www.w3.org/TR/REC-html40/types.html#type-cdata
	 *
	 * @param array|string $val String to escape, or array of strings
	 *
	 * @return array|string
	 */
	public static function raw2htmlid($val)
	{
		if (is_array($val)) {
			foreach ($val as $k => $v) {
				$val[$k] = self::raw2htmlid($v);
			}

			return $val;
		} else {
			return trim(preg_replace(
					'/_+/', '_', preg_replace('/[^a-zA-Z0-9\-_:.]+/', '_', $val)),
				'_'
			);
		}
	}

	public static function map_array_to_i18n(array $array, $i18nPrefix = '', $replacements = [])
	{
		if ($i18nPrefix)
			$i18nPrefix = trim($i18nPrefix, '.') . '.';

		$map = [];

		foreach ($array as $key => $value) {
			if (is_array($value))
				$map[$key] = static::map_array_to_i18n($value, $i18nPrefix, $replacements);
			else
				$map[$key] = _t($i18nPrefix . $key, \FormField::name_to_label($value), $replacements);
		}

		return $map;
	}

	public static function is_instanceof($class, $page = null)
	{
		if (!$page)
			$page = \Controller::curr();

		if ($page->hasMethod('data'))
			$page = $page->data();

		$obj = singleton($class);

		return $page instanceof $obj;
	}

	public static function isFrontendEditingEnabled()
	{
		return \ClassInfo::exists('FrontendEditing') && \FrontendEditing::editingEnabled();
	}
} 