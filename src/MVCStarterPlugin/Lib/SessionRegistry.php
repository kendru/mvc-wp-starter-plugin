<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib;

use MVCStarterPlugin\Lib\Ideals\Registry;

class SessionRegistry implements Registry
{
	/** @type SessionRegistry the single instance of this class */
	private static $instance;
	/** @type int the conflict mode for the registry */
	private $conflictMode;

	private function __construct()
	{
		if (session_id() === '') {
			session_start();
		}
		$this->conflictMode = self::CONFLICT_OVERWRITE;
	}
	public static function instance()
	{
		return empty(self::$instance)
			? (self::$instance = new self())
			: self::$instance;
	}

	public function get($key)
	{
		return isset($_SESSION[__CLASS__][$key]) ? $_SESSION[__CLASS__][$key] : null;
	}

	public function set($key, $value)
	{
		if (!isset($_SESSION[__CLASS__][$key])) {
			$_SESSION[__CLASS__][$key] = $value;
		} else {
			switch ($this->conflictMode) {
				case self::CONFLICT_OVERWRITE:
					$_SESSION[__CLASS__][$key] = $value;
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

		// This is only executed when the key was successfully set
		return $value;
	}

	public function getLoggedInUser()
	{
		return $this->get('logged_in_user');
	}

	public function setLoggedInUser($username)
	{
		return $this->set('logged_in_user', $username);
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
}