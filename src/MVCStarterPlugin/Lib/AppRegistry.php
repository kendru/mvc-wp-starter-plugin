<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib;

use MVCStarterPlugin\Lib\Ideals\Registry;

/**
 * Application registry
 * This class is used for getting and setting application-wide
 * options. It uses WordPress options under the hood, so there
 * is not locking strategy implemented. This is designed to be
 * used as a <em>primarily</em> read-only registry, with writes
 * happening only from plugin settings pages. 
 */
class AppRegistry implements Registry
{
	/** @type SessionRegistry the single instance of this class */
	private static $instance;
	/** @type int the conflict mode for the registry */
	private $conflictMode;
	/** @type array the registry, implemented as an associative array */
	private $reg;
	/** @type bool dirty flag, used to determine if registry has been changed */
	private $is_dirty = false;
	/** @type \MVCStarterPlugin\Application $app reference to the parent application */
	private $app;

	private function __construct($application)
	{
		if (!function_exists('get_option')) {
			throw new \Exception("WordPress environment has not been loaded");
		}

		$this->app = $application;

		$option = get_option($this->getOptionName(), array());
		
		// Populate registry from config file on first run
		if (empty($option)) {
			try {
				\Symfony\Component\Yaml\Yaml::enablePhpParsing();
				$option = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->app->getConfigDir() . 'app.yaml'));	
			} catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
				$option = array();
			}

			add_option($this->getOptionName(), $option, '', 'no');
		}

		$this->reg = $option;
		$this->conflictMode = self::CONFLICT_OVERWRITE;
	}

	public static function instance($application)
	{
		return empty(self::$instance)
			? (self::$instance = new self($application))
			: self::$instance;
	}

	public function get($key)
	{
		return isset($this->reg[$key]) ? $this->reg[$key] : null;
	}

	public function set($key, $value)
	{
		if (!isset($this->reg[$key])) {
			$this->reg[$key] = $value;
		} else {
			switch ($this->conflictMode) {
				case self::CONFLICT_OVERWRITE:
					$this->reg[$key] = $value;
					break;

				case self::CONFLICT_EXCEPTION:
					throw new \Exception("Cannot overwrite an existing registry key");
					break;

				case self::CONFLICT_SILENT:
					return null;
					break;	

				default:
					throw new \Exception("Conflict mode not recognized");
					break;
			}
		}

		// The following two lines are only executed when the key was successfully set
		$this->is_dirty = true; // Ensure that registry is updated
		return $value;
	}

	public function save()
	{
		if ($this->is_dirty) {
			update_option($this->getOptionName(), $this->reg);
		}
	}

	public function setConflictMode($mode)
	{
		switch ($mode) {
			case self::CONFLICT_OVERWRITE:
				// falls through
			case self::CONFLICT_EXCEPTION:
				// falls through
			case self::CONFLICT_SILENT:
				$this->conflictMode = $mode;
				break;	
			default:
				throw new \Exception("Invalid conflict mode provided");
				break;
		}
	}

	private function getOptionName()
	{
		return "mvc_" . md5(__FILE__);
	}
}