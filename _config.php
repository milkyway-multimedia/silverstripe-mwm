<?php

if (!defined('SS_MWM_DIR'))
	define('SS_MWM_DIR', basename(rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR)));

Requirements::set_backend(new \Milkyway\SS\Assets_Backend());

// Register all shortcodes for use with injector and shortcodable module
\Milkyway\SS\Modules\ShortcodableController::register();

if (!function_exists('with')) {
	function with($object)
	{
		return $object;
	}
}