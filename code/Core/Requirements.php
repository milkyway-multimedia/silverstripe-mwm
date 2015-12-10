<?php namespace Milkyway\SS\Core;

/**
 * Milkyway Multimedia
 * Requirements.php
 *
 * @package milkyway-multimedia/ss-mwm
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use Requirements as Original;
use Config;
use SS_Cache;

class Requirements extends Original implements \Flushable
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
        'first'       => [
            'css' => [],
            'js'  => [],
        ],
        'last'        => [
            'css' => [],
            'js'  => [],
        ],
        'defer'       => [
            'css' => [],
            'js'  => [],
        ],
        'inline'      => [
            'css' => [],
            'js'  => [],
        ],
        'inline-head' => [
            'css' => [],
            'js'  => [],
        ],
    ];

    protected static $replace = [];
    protected static $block_ajax = [];

    public static function config()
    {
        return Config::inst()->forClass('Milkyway_Assets');
    }

    public static function get_files_by_type($type, $where = 'first')
    {
        if (isset(self::$files[$where]) && isset(self::$files[$where][$type])) {
            return self::$files[$where][$type];
        }

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

    public static function add($files, $where = 'first', $before = '', $override = '')
    {
        if (is_string($files)) {
            $files = [$files];
        }

        if (!isset(self::$files[$where])) {
            return;
        }

        foreach ($files as $file) {
            $type = $override ?: strtok(strtok(pathinfo($file, PATHINFO_EXTENSION), '#'), '?');

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
                    if ($type == 'css') {
                        self::$files[$where][$type][$file] = ['media' => ''];
                    } else {
                        self::$files[$where][$type][$file] = true;
                    }
                }
            }
        }
    }

    public static function before($files, $before = '', $where = '')
    {
        if($where) {
            return static::add($files, $where, $before);
        }

       static::backend()->before($files, $before);
    }

    public static function after($files, $after = '', $where = '')
    {
        static::backend()->after($files, $after);
    }

    public static function remove($files, $where = '')
    {
        if (is_string($files)) {
            $files = [$files];
        }

        if ($where && !isset(self::$files[$where])) {
            return;
        }

        foreach ($files as $file) {
            if ($where) {
                if (isset(self::$files[$where][$file])) {
                    unset(self::$files[$where][$file]);
                }
            } else {
                foreach (self::$files as $where => $files) {
                    if (isset($files[$file])) {
                        unset($files[$file]);
                    }
                }
            }
        }
    }

    // Load a requirement as a deferred file (loaded using Google Async)
    public static function defer($file, $before = '', $override = '')
    {
        self::add($file, 'defer', $before, $override);
    }

    public static function undefer($file)
    {
        self::remove($file, 'defer');
    }

    public static function inline($file, $top = false, $before = '', $override = '')
    {
        if ($top) {
            self::add($file, 'inline-head', $before, $override);
        } else {
            self::add($file, 'inline', $before, $override);
        }
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
        if (isset(self::$replace[$file])) {
            unset(self::$replace[$file]);
        } elseif (($key = array_search($file, self::$replace)) && $key !== false) {
            unset(self::$replace[$key]);
        }
    }

    public static function block_ajax($file)
    {
        self::$block_ajax[$file] = true;
    }

    public static function unblock_ajax($file)
    {
        if (isset(self::$block_ajax[$file])) {
            unset(self::$block_ajax[$file]);
        } elseif (($key = array_search($file, self::$block_ajax)) && $key !== false) {
            unset(self::$block_ajax[$key]);
        }
    }

    public static function head($file)
    {
        if ($file && (Director::is_absolute_url($file) || Director::fileExists($file)) && ($ext = pathinfo($file,
                PATHINFO_EXTENSION)) && ($ext == 'js' || $ext == 'css')
        ) {
            $file = Director::is_absolute_url($file) ? $file : \Controller::join_links(Director::baseURL(),
                static::get_cache_busted_file_url($file));

            if ($ext == 'js') {
                static::insertHeadTags('<script src="' . $file . '"></script>', $file);
            } else {
                static::insertHeadTags('<link href="' . $file . '" rel="stylesheet" />', $file);
            }
        }
    }

    public static function block_default()
    {
        $blocked = (array)self::config()->block;

        if (empty($blocked)) {
            return;
        }

        foreach ($blocked as $block) {
            preg_match_all('/{{([^}]*)}}/', $block, $matches);

            if (!isset($matches[1]) || empty($matches[1])) {
                continue;
            }

            foreach ($matches[1] as $match) {
                if (strpos($match, '|') !== false) {
                    list($const, $default) = explode('|', $match);
                } else {
                    $const = $default = $match;
                }

                if (defined(trim($const))) {
                    $block = str_replace('{{' . $match . '}}', constant(trim($const)), $block);
                } elseif (trim($default)) {
                    $block = str_replace('{{' . $match . '}}', trim($default), $block);
                }
            }

            Requirements::block($block);
        }
    }

    public static function get_cache_busted_file_url($file)
    {
        if ($ext = pathinfo($file, PATHINFO_EXTENSION)) {
            if ($ext == 'js' || $ext == 'css') {
                $myExt = strstr($file, 'combined.' . $ext) ? 'combined.' . $ext : $ext;
                $filePath = preg_replace('/\?.*/', '', Director::baseFolder() . '/' . $file);

                $mTime = Requirements::get_suffix_requirements() ? "." . filemtime($filePath) : '';

                $suffix = '';
                if (strpos($file, '?') !== false) {
                    $suffix = substr($file, strpos($file, '?'));
                }

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
            'function ' . $function,
            $function,
            json_encode($css, JSON_UNESCAPED_SLASHES),
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
            'function ' . $function,
            $function,
            json_encode(array_keys($scripts), JSON_UNESCAPED_SLASHES),
        ], $script);
    }

    public static function utilities_js()
    {
        if (Director::isDev()) {
            $script = @file_get_contents(Director::getAbsFile(SS_MWM_DIR . '/js/mwm.utilities.js'));
        } else {
            $script = static::cache()->load('JS__utilities');

            if (!$script) {
                require_once(THIRDPARTY_PATH . DIRECTORY_SEPARATOR . 'jsmin' . DIRECTORY_SEPARATOR . 'jsmin.php');
                $script = \JSMin::minify(@file_get_contents(\Director::getAbsFile(SS_MWM_DIR . '/js/mwm.utilities.js')));
                static::cache()->save($script, 'JS__utilities');
            }
        }

        static::insertHeadTags('<script>' . $script . '</script>', 'JS-MWM-Utilities');
    }

    public static function include_font_css()
    {
        if ($fonts = static::config()->font_css) {
            if (!is_array($fonts)) {
                $fonts = [$fonts];
            }

            static::add($fonts);
        } else {
            static::add('https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');
        }
    }

    public static function minify_contents_according_to_type($contents, $file)
    {
        $type = strtok(strtok(pathinfo($file, PATHINFO_EXTENSION), '#'), '?');

        if ($type == 'js') {
            require_once(THIRDPARTY_PATH . DIRECTORY_SEPARATOR . 'jsmin' . DIRECTORY_SEPARATOR . 'jsmin.php');
            return \JSMin::minify($contents);
        } elseif ($type == 'css') {
            return str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '',
                preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $contents));
        }

        return $contents;
    }

    protected static $cache;

    public static function cache()
    {
        if (!static::$cache) {
            static::$cache = SS_Cache::factory('Milkyway_SS_Assets', 'Output', ['lifetime' => 20000 * 60 * 60]);
        }

        return static::$cache;
    }

    public static function flush()
    {
        static::cache()->clean();
    }
}