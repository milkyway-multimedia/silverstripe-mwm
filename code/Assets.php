<?php namespace Milkyway\SS;

class Assets {
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

    /** @var array Disable replacment files for specific controllers */
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

    public static function config() {
        return \Config::inst()->forClass('Milkyway_Assets');
    }

    protected static $replace = [];

	public static function get_files_by_type($type, $where = 'first') {
		if(isset(self::$files[$where]) && isset(self::$files[$where][$type]))
            return self::$files[$where][$type];

		return [];
	}

    public static function replacements() {
        return self::$replace;
    }

	public static function add($files, $where = 'first', $before = '') {
		if (is_string($files)) $files = [$files];

        if(!isset(self::$files[$where]))
            return;

        foreach ($files as $file) {
            $type = strtok(strtok(pathinfo($file, PATHINFO_EXTENSION), '#'), '?');

            if ($type == 'css' || $type == 'js') {
                if($before && isset(self::$files[$where][$before])) {
                    $i = 0;
                    foreach(self::$files[$where][$type] as $key => $ret) {
                        if($key == $before) {
                            array_splice(self::$files[$where][$type], $i, 0, [$file => $ret]);
                            break;
                        }

                        $i++;
                    }
                }
                else {
                    if($type == 'css')
                        self::$files[$where][$type][$file] = array('media' => '');
                    else
                        self::$files[$where][$type][$file] = true;
                }
            }
        }
	}

	public static function remove($files, $where = '') {
		if (is_string($files)) $files = [$files];

        if($where && !isset(self::$files[$where]))
            return;

        foreach ($files as $file) {
            if($where) {
                if(isset(self::$files[$where][$file]))
                    unset(self::$files[$where][$file]);
            }
            else {
                foreach(self::$files as $where => $files) {
                    if(isset($files[$file]))
                        unset($files[$file]);
                }
            }
        }
	}

	// Load a requirement as a deferred file (loaded using Google Async)
	public static function defer($file, $before = '') {
		self::add($file, 'defer', $before);
	}

	public static function undefer($file) {
		self::remove($file, 'defer');
	}

	public static function inline($file, $top = false, $before = '') {
        if($top)
            self::add($file, 'inline-head', $before);
        else
            self::add($file, 'inline', $before);
	}

	public static function outline($file) {
		self::remove($file, 'inline-head');
		self::remove($file, 'inline');
	}

	// Replace a requirement file with another
	public static function replace($old, $new) {
        self::$replace[$old] = $new;
	}

	public static function unreplace($file) {
		if(isset(self::$replace[$file]))
			unset(self::$replace[$file]);
		elseif(($key = array_search($file, self::$replace)) && $key !== false)
			unset(self::$replace[$key]);
	}

    public static function head($file) {
        if($file && Director::fileExists($file) && ($ext = pathinfo($file, PATHINFO_EXTENSION)) && ($ext == 'js' || $ext == 'css')) {
            if($ext == 'js')
                \Requirements::insertHeadTags('<script src="' . self::get_cache_busted_file_url($file) . '"></script>', $file);
            else
                \Requirements::insertHeadTags('<link href="' . self::get_cache_busted_file_url($file) . '" rel="stylesheet" />', $file);
        }
    }

    public static function block() {
        $blocked = (array) self::config()->block;

        if(count($blocked)) {
            foreach($blocked as $block) {
                preg_match_all('/{{([^}]*)}}/', $block, $matches);

                if(isset($matches[1]) && count($matches[1])) {
                    foreach($matches[1] as $match) {
                        if(strpos($match, '|') !== false)
                            list($const, $default) = explode('|', $match);
                        else {
                            $const = $default = $match;
                        }

                        if(defined(trim($const)))
                            $block = str_replace('{{' . $match . '}}', constant(trim($const)), $block);
                        elseif(trim($default))
                            $block = str_replace('{{' . $match . '}}', trim($default), $block);
                    }
                }

                \Requirements::block($block);
            }
        }
    }

    public static function get_cache_busted_file_url($file) {
        if($ext = pathinfo($file, PATHINFO_EXTENSION)) {
            if($ext == 'js' || $ext == 'css') {
                $myExt = strstr($file, 'combined.' . $ext) ? 'combined.' . $ext : $ext;
                $filePath = preg_replace('/\?.*/', '', \Director::baseFolder() . '/' . $file);

                $mTime = \Requirements::get_suffix_requirements() ? "." . filemtime($filePath) : '';

                $suffix = '';
                if(strpos($file, '?') !== false)
                    $suffix = substr($file, strpos($file, '?'));

                return str_replace('.' . $myExt, '', $file) . "{$mTime}.{$myExt}{$suffix}";
            }
        }

        return false;
    }

    public static function js_attach_to_event() {
        \Requirements::insertHeadTags('
	<script>
		function attachToEvent(element, event, callback) {
			if(element.addEventListener)
				element.addEventListener(event, callback, false);
			else if(element.attachEvent)
				element.attachEvent(event, callback);
			else {
				var m = "on" + event;
				if(element.hasOwnProperty(m))
					element["m"] = callback;
			}
		}
	</script>
		', 'JS-EventAttachment');
    }

    public static function defer_css(array $css, $function = 'css') {
        return '
    function ' . $function . '() {
		var element,
			files = ' . json_encode($css, JSON_UNESCAPED_SLASHES) . ',
			links = document.getElementsByTagName("link"),
			included = false;

		for (var file in files) {
			if (files.hasOwnProperty(file)) {
				for (var j = links.length; j--;) {
			        if (links[j].href == file) {
						included = true;
						break;
					}
			    }

			    if(included) {
			        included = false;
			        continue;
			    }

				element = document.createElement("link");
				element.href = file;
				element.rel = "stylesheet";
				element.type = "text/css";

				if (files.file.media)
					element.media = files.file.media;

				document.getElementsByTagName("head")[0].appendChild(element);
			}
		}
	}';
    }

    public static function defer_scripts(array $scripts, $function = 'js') {
        return '
    function ' . $function . '() {
		var element,
			files = ' . json_encode(array_keys($scripts), JSON_UNESCAPED_SLASHES) . ',
			scripts = document.getElementsByTagName("script"),
			included = false;

		for (var i = 0; i < files.length; i++) {
			for (var j = scripts.length; j--;) {
		        if (scripts[j].src == files[i]) {
					included = true;
					break;
				}
		    }

		    if(included) {
		        included = false;
		        continue;
		    }

		    element = document.createElement("script");
		    element.src = files[i];
		    document.getElementsByTagName("body")[0].appendChild(element);
		}
	}';
    }
}

class Assets_Backend extends \Requirements_Backend {
	protected function path_for_file($fileOrUrl) {
        if(!Assets::$use_cache_busted_file_extensions)
            return parent::path_for_file($fileOrUrl);

		if(preg_match('{^//|http[s]?}', $fileOrUrl))
			return $fileOrUrl;
		elseif(\Director::fileExists($fileOrUrl))
			return \Controller::join_links(\Director::baseURL(), Assets::get_cache_busted_file_url($fileOrUrl));
		else
			return false;
	}

	public function customScript($script, $uniquenessID = null) {
		if(strpos($script, 'MemberLoginForm')) return '';

		if($uniquenessID) $this->customScript[$uniquenessID] = $script;
		else $this->customScript[] = $script;

		$script .= "\n";

		return $script;
	}

	private $_response;

	public function includeInHTML($templateFile, $content) {
		$this->assets();
		$body = parent::includeInHTML($templateFile, $content);
		$this->extras();
		return $body;
	}

	public function include_in_response(\SS_HTTPResponse $response) {
		$this->assets();
		parent::include_in_response($response);
		if(\Director::is_ajax())
			$this->_response = $response;
		$this->extras();
	}

	// Allow JS and CSS to be deferred even when called via ajax
	protected function extras() {
		if($this->_response) {
			if($this->customScript && count($this->customScript)) {
				$scripts = '';

				foreach(array_diff_key($this->customScript, $this->blocked) as $script) {
					$scripts .= "<script type=\"text/javascript\">\n";
					$scripts .= "$script\n";
					$scripts .= "</script>\n";
				}

				$body = $this->_response->getBody();

				$end = stripos($body, '</body>');

				if($end !== false)
					$body = preg_replace("/(<\/body[^>]*>)/i", $scripts . "\\1", $body);
				else
					$body .= $scripts;

				$this->_response->setBody($body);
			}

			$this->_response = null;
		}
	}

	protected function assets() {
		$firstCss = Assets::get_files_by_type('css');
		$firstJs = Assets::get_files_by_type('js');
		$lastCss = Assets::get_files_by_type('css', 'last');
		$lastJs = Assets::get_files_by_type('js', 'last');

		$this->css = array_merge(($firstCss + $this->css), $lastCss);
		$this->javascript = array_merge(($firstJs + $this->javascript), $lastJs);

        $this->issueReplacements();

        $inline = array_merge(Assets::get_files_by_type('css', 'inline'), Assets::get_files_by_type('css', 'inline-head'));
        $this->inlineFiles($inline, 'customCSS', 'css', '%s', 'Inline-CSS');

        $this->inlineFiles(Assets::get_files_by_type('js', 'inline-head'), 'customHeadTags', 'javascript', '<script type="text/javascript">%s</script>', 'Inline-JS-Head');
        $this->inlineFiles(Assets::get_files_by_type('js', 'inline'), 'customScript', 'javascript', '%s', 'Inline-JS');

        $deferred = Assets::get_files_by_type('css', 'defer');
        $time = time();

		if(count($deferred)) {
            foreach($deferred as $file => $data) {
                $this->removeIfFound($file, 'css');

                $this->removeIfFound('Deferred-CSS', 'customScript');

                $function = 'js' . $time;
                $script = Assets::defer_css($deferred, $function);

				if(\Director::is_ajax()) {
					$script .= '
	' . $function . '();
					';
				}
				else {
					Assets::js_attach_on_event();
					$script .= '
	attachToEvent(window, "load", ' . $function . ');
					';
				}

				$this->customScript($script, 'Deferred-CSS');
			}
        }

        $deferred = Assets::get_files_by_type('js', 'defer');

        if(count($deferred)) {
            foreach($deferred as $file => $data) {
                $this->removeIfFound($file, 'javascript');
                $this->removeIfFound('Deferred-JS', 'customScript');

                $function = 'js' . $time;

				$script = Assets::defer_scripts($deferred, $function);

				if(\Director::is_ajax()) {
					$script .= '
	' . $function . '();
					';
				}
				else {
					Assets::js_attach_to_event();
					$script .= '
	attachToEvent(window, "load", ' . $function . ');
					';
				}

				$this->customScript($script, 'Deferred-JS');
			}
		}
	}

    protected function inlineFiles($inlines, $setVar = 'customCSS', $unsetVar = 'css', $replaceString = '%s', $id = 'Inline-CSS')
    {
        if (count($inlines))
        {
            $this->removeIfFound($id, $setVar);

            $items = [];

            foreach ($inlines as $file => $data)
            {
                if(!\Director::is_absolute_url($file))
                    $file = \Director::getAbsFile($file);
                $content = @file_get_contents($file);

                if ($content)
                {
                    $items[$file] = $content;

                    $this->removeIfFound($id, $unsetVar);
                }
            }

            if (count($items)) {
                if($setVar == 'customHeadTags') {
                    $this->insertHeadTags(
                        sprintf($replaceString, implode("\n\n", $items)),
                       $id
                    );
                }
                elseif($setVar == 'customScript') {
                    $this->customScript(
                        sprintf($replaceString, implode("\n\n", $items)),
                        $id
                    );
                }
                elseif($setVar == 'customCSS') {
                    $this->customCSS(
                        sprintf($replaceString, implode("\n\n", $items)),
                        $id
                    );
                }
            }
        }
    }

    protected function removeIfFound($file, $var = 'css') {
        if(isset($this->{$var}[$file]))
            unset($this->{$var}[$file]);
    }

    protected function issueReplacements()
    {
        foreach(Assets::$disable_replaced_files_for as $class) {
            if (is_a($this->owner, $class))
               return;
        }

        $replaced = Assets::replacements();

        if (count($replaced))
        {
            foreach ($replaced as $old => $new)
            {
                if (isset($this->css[$old]))
                {
                    $old = $this->css[$old];
                    unset($this->css[$old]);
                    $this->css[$new] = $old;
                } elseif (isset($this->javascript[$old]))
                {
                    unset($this->javascript[$old]);
                    $this->javascript[$new] = true;
                } elseif (isset($this->customScript[$old]))
                {
                    unset($this->customScript[$old]);
                    $this->customScript[$new] = true;
                } elseif (isset($this->customCSS[$old]))
                {
                    unset($this->customCSS[$old]);
                    $this->customCSS[$new] = true;
                } elseif (isset($this->customHeadTags[$old]))
                {
                    unset($this->customHeadTags[$old]);
                    $this->customHeadTags[$new] = true;
                }
            }
        }
    }
}