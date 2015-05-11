<?php namespace Milkyway\SS;

use League\Event\Event;

class Assets extends \Requirements implements \Flushable
{
	/** @var bool Append the cache busting id as a file extension rather than as a query string */
	public static $use_cache_busted_file_extensions = false;

	/** @var array Disable cache busted file extensions for specific controllers */
	public static $disable_cache_busted_file_extensions_for = [
		'LeftAndMain',
	];

	/** @var array Disable blocked files for specific controllers */
	public static $disable_blocked_files_for = [
		'LeftAndMain',
	];

	/** @var array Disable replacement files for specific controllers */
	public static $disable_replaced_files_for = [];

	protected static $files = [
		'first' => [
			'css' => [],
			'js' => [],
		],
		'last' => [
			'css' => [],
			'js' => [],
		],
		'defer' => [
			'css' => [],
			'js' => [],
		],
		'inline' => [
			'css' => [],
			'js' => [],
		],
		'inline-head' => [
			'css' => [],
			'js' => [],
		],
	];

	protected static $replace = [];
	protected static $block_ajax = [];

	public static function config()
	{
		return \Config::inst()->forClass('Milkyway_Assets');
	}

	public static function get_files_by_type($type, $where = 'first')
	{
		if (isset(self::$files[$where]) && isset(self::$files[$where][$type]))
			return self::$files[$where][$type];

		return [];
	}

	public static function replacements()
	{
		return self::$replace;
	}

	public static function get_block_ajax()
	{
		return self::$block_ajax;
	}

	public static function add($files, $where = 'first', $before = '')
	{
		if (is_string($files)) $files = [$files];

		if (!isset(self::$files[$where]))
			return;

		foreach ($files as $file) {
			$type = strtok(strtok(pathinfo($file, PATHINFO_EXTENSION), '#'), '?');

			if ($type == 'css' || $type == 'js') {
				if ($before && isset(self::$files[$where][$before])) {
					$i = 0;
					foreach (self::$files[$where][$type] as $key => $ret) {
						if ($key == $before) {
							array_splice(self::$files[$where][$type], $i, 0, [$file => $ret]);
							break;
						}

						$i++;
					}
				} else {
					if ($type == 'css')
						self::$files[$where][$type][$file] = array('media' => '');
					else
						self::$files[$where][$type][$file] = true;
				}
			}
		}
	}

	public static function remove($files, $where = '')
	{
		if (is_string($files)) $files = [$files];

		if ($where && !isset(self::$files[$where]))
			return;

		foreach ($files as $file) {
			if ($where) {
				if (isset(self::$files[$where][$file]))
					unset(self::$files[$where][$file]);
			} else {
				foreach (self::$files as $where => $files) {
					if (isset($files[$file]))
						unset($files[$file]);
				}
			}
		}
	}

	// Load a requirement as a deferred file (loaded using Google Async)
	public static function defer($file, $before = '')
	{
		self::add($file, 'defer', $before);
	}

	public static function undefer($file)
	{
		self::remove($file, 'defer');
	}

	public static function inline($file, $top = false, $before = '')
	{
		if ($top)
			self::add($file, 'inline-head', $before);
		else
			self::add($file, 'inline', $before);
	}

	public static function outline($file)
	{
		self::remove($file, 'inline-head');
		self::remove($file, 'inline');
	}

	// Replace a requirement file with another
	public static function replace($old, $new)
	{
		self::$replace[$old] = $new;
	}

	public static function unreplace($file)
	{
		if (isset(self::$replace[$file]))
			unset(self::$replace[$file]);
		elseif (($key = array_search($file, self::$replace)) && $key !== false)
			unset(self::$replace[$key]);
	}

	public static function block_ajax($file)
	{
		self::$block_ajax[$file] = true;
	}

	public static function unblock_ajax($file)
	{
		if (isset(self::$block_ajax[$file]))
			unset(self::$block_ajax[$file]);
		elseif (($key = array_search($file, self::$block_ajax)) && $key !== false)
			unset(self::$block_ajax[$key]);
	}

	public static function head($file)
	{
		if ($file && (Director::is_absolute_url($file) || Director::fileExists($file)) && ($ext = pathinfo($file, PATHINFO_EXTENSION)) && ($ext == 'js' || $ext == 'css')) {
			$file = Director::is_absolute_url($file) ? $file : \Controller::join_links(Director::baseURL(), static::get_cache_busted_file_url($file));

			if ($ext == 'js')
				static::insertHeadTags('<script src="' . $file . '"></script>', $file);
			else
				static::insertHeadTags('<link href="' . $file . '" rel="stylesheet" />', $file);
		}
	}

	public static function block_default()
	{
		$blocked = (array)self::config()->block;

		if (count($blocked)) {
			foreach ($blocked as $block) {
				preg_match_all('/{{([^}]*)}}/', $block, $matches);

				if (isset($matches[1]) && count($matches[1])) {
					foreach ($matches[1] as $match) {
						if (strpos($match, '|') !== false)
							list($const, $default) = explode('|', $match);
						else {
							$const = $default = $match;
						}

						if (defined(trim($const)))
							$block = str_replace('{{' . $match . '}}', constant(trim($const)), $block);
						elseif (trim($default))
							$block = str_replace('{{' . $match . '}}', trim($default), $block);
					}
				}

				\Requirements::block($block);
			}
		}
	}

	public static function get_cache_busted_file_url($file)
	{
		if ($ext = pathinfo($file, PATHINFO_EXTENSION)) {
			if ($ext == 'js' || $ext == 'css') {
				$myExt = strstr($file, 'combined.' . $ext) ? 'combined.' . $ext : $ext;
				$filePath = preg_replace('/\?.*/', '', \Director::baseFolder() . '/' . $file);

				$mTime = \Requirements::get_suffix_requirements() ? "." . filemtime($filePath) : '';

				$suffix = '';
				if (strpos($file, '?') !== false)
					$suffix = substr($file, strpos($file, '?'));

				return str_replace('.' . $myExt, '', $file) . "{$mTime}.{$myExt}{$suffix}";
			}
		}

		return false;
	}

	public static function js_attach_to_event()
	{
		static::utilities_js();
	}

	public static function defer_css(array $css, $function = 'css')
	{
		static::utilities_js();
		$script = static::cache()->load('JS__DeferCSS');

		if (!$script) {
			require_once(THIRDPARTY_PATH . DIRECTORY_SEPARATOR . 'jsmin' . DIRECTORY_SEPARATOR . 'jsmin.php');
			$script = \JSMin::minify('
				function {$FUNCTION}() {
					if(window.mwm && window.mwm.hasOwnProperty("utilities") && window.mwm.utilities.hasOwnProperty("deferCssFiles")) {
						window.mwm.utilities.deferCssFiles({$FILES});
					}
				};
		    ');
			static::cache()->save($script, 'JS__DeferCSS');
		}

		return str_replace(['function{$FUNCTION}', '{$FUNCTION}', '{$FILES}'], [
			'function ' . $function, $function, json_encode($css, JSON_UNESCAPED_SLASHES)
		], $script);
	}

	public static function defer_scripts(array $scripts, $function = 'js')
	{
		static::utilities_js();
		$script = static::cache()->load('JS__DeferJS');

		if (!$script) {
			require_once(THIRDPARTY_PATH . DIRECTORY_SEPARATOR . 'jsmin' . DIRECTORY_SEPARATOR . 'jsmin.php');
			$script = \JSMin::minify('
			    function {$FUNCTION}() {
					if(window.mwm && window.mwm.hasOwnProperty("utilities") && window.mwm.utilities.hasOwnProperty("deferJsFiles")) {
						window.mwm.utilities.deferJsFiles({$FILES});
					}
				};
			');
			static::cache()->save($script, 'JS__DeferJS');
		}

		return str_replace(['function{$FUNCTION}', '{$FUNCTION}', '{$FILES}'], [
			'function ' . $function, $function, json_encode(array_keys($scripts), JSON_UNESCAPED_SLASHES)
		], $script);
	}

	public static function utilities_js()
	{
		$script = static::cache()->load('JS__utilities');

		if (!$script) {
			require_once(THIRDPARTY_PATH . DIRECTORY_SEPARATOR . 'jsmin' . DIRECTORY_SEPARATOR . 'jsmin.php');
			$script = \JSMin::minify(@file_get_contents(\Director::getAbsFile(SS_MWM_DIR . '/javascript/mwm.utilities.js')));
			static::cache()->save($script, 'JS__utilities');
		}

		\Requirements::insertHeadTags('<script>' . $script . '</script>', 'JS-MWM-Utilities');
	}

	public static function include_font_css()
	{
		if ($fonts = static::config()->font_css) {
			if (!is_array($fonts))
				$fonts = [$fonts];

			static::add($fonts);
		} else
			static::add(SS_MWM_DIR . '/thirdparty/font-awesome/css/font-awesome.min.css');
	}

	public static function minify_contents_according_to_type($contents, $file) {
		$type = strtok(strtok(pathinfo($file, PATHINFO_EXTENSION), '#'), '?');

		if($type == 'js') {
			require_once(THIRDPARTY_PATH . DIRECTORY_SEPARATOR .'jsmin' . DIRECTORY_SEPARATOR . 'jsmin.php');
			return \JSMin::minify($contents);
		}
		elseif($type == 'css') {
			return str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $contents));
		}

		return $contents;
	}

	protected static $cache;

	public static function cache()
	{
		if (!static::$cache)
			static::$cache = \SS_Cache::factory('Milkyway_SS_Assets', 'Output', ['lifetime' => 20000 * 60 * 60]);

		return static::$cache;
	}

	public static function flush() {
		static::cache()->clean();
	}
}

class Assets_Backend extends \Requirements_Backend
{
	public function javascriptTemplate($file, $vars, $uniquenessID = null)
	{
		if (defined('INFOBOXES_DIR') && $file == INFOBOXES_DIR . '/javascript/InfoBoxes.js') {
			$uniquenessID = INFOBOXES_DIR . '/javascript/InfoBoxes.js';
			Assets::block_ajax($uniquenessID);

			if(isset($vars['Data']) && $vars['Data'] === ']')
				$vars['Data'] = '[]';
		}

		return parent::javascriptTemplate($file, $vars, $uniquenessID);
	}

	public function add_i18n_javascript($langDir, $return = false, $langOnly = false)
	{
		if (!in_array($langDir, $this->blocked) && !isset($this->blocked[$langDir]))
			return parent::add_i18n_javascript($langDir, $return, $langOnly);

		return $return ? [] : null;
	}

	protected function path_for_file($fileOrUrl)
	{
		if (!Assets::$use_cache_busted_file_extensions)
			return parent::path_for_file($fileOrUrl);

		if (preg_match('{^//|http[s]?}', $fileOrUrl))
			return $fileOrUrl;
		elseif (\Director::fileExists($fileOrUrl))
			return \Controller::join_links(\Director::baseURL(), Assets::get_cache_busted_file_url($fileOrUrl));
		else
			return false;
	}

	public function customScript($script, $uniquenessID = null)
	{
		if (strpos($script, 'MemberLoginForm')) return '';
		if (strpos($script, 'http://suggestqueries.google.com/complete/search') !== -1 && !$uniquenessID) {
			$uniquenessID = 'googlesuggestfield-script';
			Assets::block_ajax($uniquenessID);
		}

		if ($uniquenessID) $this->customScript[$uniquenessID] = $script;
		else $this->customScript[] = $script;

		$script .= "\n";

		return $script;
	}

	private $_response;

	public function includeInHTML($templateFile, $content)
	{
		$eventful = singleton('Eventful');

		if($eventful) {
			$eventful->fire('assets:beforeProcessHtml', $templateFile, $content);
		}

		$this->assets();
		$body = parent::includeInHTML($templateFile, $content);
		$this->attachCustomScriptsToResponse();

		if($eventful) {
			$eventful->fire('assets:afterProcessHtml', $body, $templateFile, $content);
		}

		return $body;
	}

	public function include_in_response(\SS_HTTPResponse $response)
	{
		$eventful = singleton('Eventful');

		if($eventful) {
			$eventful->fire('assets:beforeProcessResponse', $response);
		}

		$this->assets();

		parent::include_in_response($response);
		if (\Director::is_ajax())
			$this->_response = $response;
		$this->attachCustomScriptsToResponse();

		if($eventful) {
			$eventful->fire('assets:afterProcessResponse', $response);
		}
	}

	/*
	 * Allow JS and CSS to be deferred even when called via ajax
	 * @todo Does not work in CMS, which uses jquery ondemand anyway
	 */
	protected function attachCustomScriptsToResponse()
	{
		if ($this->_response) {
			if ($this->customScript && count($this->customScript)) {
				$scripts = '';

				foreach (array_diff_key($this->customScript, $this->blocked, Assets::get_block_ajax()) as $name => $script) {
					$scripts .= "<script type=\"text/javascript\">\n";
					$scripts .= "$script\n";
					$scripts .= "</script>\n";
				}

				$body = $this->_response->getBody();

				$end = stripos($body, '</body>');

				if ($end !== false)
					$body = preg_replace("/(<\/body[^>]*>)/i", $scripts . "\\1", $body);
				elseif (!$this->_response->getHeader('X-Pjax') && !$this->_response->getHeader('X-DisableDeferred') && strpos($this->_response->getHeader('Content-Type'), 'text/html') !== false)
					$body .= $scripts;

				$this->_response->setBody($body);
			}

			$this->_response = null;
		}
	}

	protected function assets()
	{
		$firstCss = Assets::get_files_by_type('css');
		$firstJs = Assets::get_files_by_type('js');
		$lastCss = Assets::get_files_by_type('css', 'last');
		$lastJs = Assets::get_files_by_type('js', 'last');

		$this->css = array_merge(($firstCss + array_diff_key($this->css, $firstCss, $lastCss)), $lastCss);
		$this->javascript = array_merge(($firstJs + array_diff_key($this->javascript, $firstJs, $lastJs)), $lastJs);

		$this->issueReplacements();

		$inline = array_merge(Assets::get_files_by_type('css', 'inline'), Assets::get_files_by_type('css', 'inline-head'));
		$this->inlineFiles($inline, 'customCSS', 'css', '%s', 'Inline-CSS');

		$this->inlineFiles(Assets::get_files_by_type('js', 'inline-head'), 'customHeadTags', 'javascript', '<script type="text/javascript">%s</script>', 'Inline-JS-Head');
		$this->inlineFiles(Assets::get_files_by_type('js', 'inline'), 'customScript', 'javascript', '%s', 'Inline-JS');

		$deferred = Assets::get_files_by_type('css', 'defer');
		$time = time();

		if (count($deferred)) {
			foreach ($deferred as $file => $data) {
				$this->removeIfFound($file, 'css');

				$this->removeIfFound('Deferred-CSS', 'customScript');

				$function = 'js' . $time;
				$script = Assets::defer_css($deferred, $function);

				if (\Director::is_ajax()) {
					$script .= '
	' . $function . '();
					';
				} else {
					Assets::js_attach_to_event();
					$script .= '
	mwm.utilities.attachToEvent(window, "load", ' . $function . ');
					';
				}

				$this->customScript($script, 'Deferred-CSS');
			}
		}

		$deferred = Assets::get_files_by_type('js', 'defer');

		if (count($deferred)) {
			foreach ($deferred as $file => $data) {
				$this->removeIfFound($file, 'javascript');
				$this->removeIfFound('Deferred-JS', 'customScript');

				$function = 'js' . $time;

				$script = Assets::defer_scripts($deferred, $function);

				if (\Director::is_ajax()) {
					$script .= '
	' . $function . '();
					';
				} else {
					Assets::js_attach_to_event();
					$script .= '
	mwm.utilities.attachToEvent(window, "load", ' . $function . ');
					';
				}

				$this->customScript($script, 'Deferred-JS');
			}
		}
	}

	protected function inlineFiles($inlines, $setVar = 'customCSS', $unsetVar = 'css', $replaceString = '%s', $id = 'Inline-CSS')
	{
		if (count($inlines)) {
			$this->removeIfFound($id, $setVar);

			$items = [];
			$isDev = \Director::isDev();

			foreach ($inlines as $file => $data) {
				if (!\Director::is_absolute_url($file))
					$file = \Director::getAbsFile($file);

				$key = Utilities::clean_cache_key($file);
				$content = singleton('assets')->cache()->load($key);

				if ($content === false) {
					$content = @file_get_contents($file);

					if ($content && !$isDev) {
						$content = singleton('assets')->minify_contents_according_to_type($content, $file);
					}

					if(!$isDev)
						singleton('assets')->cache()->save($content, $key);
				}

				if($content) {
					$items[$file] = $content;
					$this->removeIfFound($id, $unsetVar);
				}
			}

			if (count($items)) {
				if ($setVar == 'customHeadTags') {
					$this->insertHeadTags(
						sprintf($replaceString, implode("\n\n", $items)),
						$id
					);
				} elseif ($setVar == 'customScript') {
					$this->customScript(
						sprintf($replaceString, implode("\n\n", $items)),
						$id
					);
				} elseif ($setVar == 'customCSS') {
					$this->customCSS(
						sprintf($replaceString, implode("\n\n", $items)),
						$id
					);
				}
			}
		}
	}

	protected function removeIfFound($file, $var = 'css')
	{
		if (isset($this->{$var}[$file]))
			unset($this->{$var}[$file]);
	}

	protected function issueReplacements()
	{
		foreach (Assets::$disable_replaced_files_for as $class) {
			if (\Controller::curr() && is_a(\Controller::curr(), $class))
				return;
		}

		$replaced = Assets::replacements();

		if (count($replaced)) {
			foreach ($replaced as $old => $new) {
				if (isset($this->css[$old])) {
					$old = $this->css[$old];
					unset($this->css[$old]);
					$this->css[$new] = $old;
				} elseif (isset($this->javascript[$old])) {
					unset($this->javascript[$old]);
					$this->javascript[$new] = true;
				} elseif (isset($this->customScript[$old])) {
					unset($this->customScript[$old]);
					$this->customScript[$new] = true;
				} elseif (isset($this->customCSS[$old])) {
					unset($this->customCSS[$old]);
					$this->customCSS[$new] = true;
				} elseif (isset($this->customHeadTags[$old])) {
					unset($this->customHeadTags[$old]);
					$this->customHeadTags[$new] = true;
				}
			}
		}
	}
}