<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib;

/**
 * Abstraction for WP global functions
 * Does nothing but attempts to pass method calls to
 * function calls in the global scope. This class is
 * not useful in itself, but it allows stubbing of WP
 * calls to simplify testing.
 * 
 * @todo create a list of pre-approved functions that will be part
 * of this API and only make the function call if the requested
 * fucntion exists in this "whitelist".
 */
class WPWrapper
{
	public function __call($function, $args) {
		return call_user_func_array($function, $args);
	}

	public static function __callStatic($function, $args) {
		return call_user_func_array($function, $args);
	}

	// Turn properties into function calls - this allows more elegant calling
	// of functions that take no parameters from Twig templates
	public function __get($property)
	{
		if (function_exists($property)) {
			return call_user_func($property);
		}
	}
}