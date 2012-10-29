<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib;

/**
 * Creates Command from query parameters
 * 
 * @todo Add support for custom routing set in a configuration file
 */
class Router
{
	private $can_resolve;
	private $command;

	public function __construct($application)
	{
		$handle = $application->getName();
		if (isset($_REQUEST[$handle . "_ctl"])
			&& isset($_REQUEST[$handle . "_cmd"])) {
			$this->can_resolve = true;
			$this->command = new Command($_REQUEST[$handle . "_ctl"], $_REQUEST[$handle . "_cmd"]);
		} else {
			$this->command = new Command();
		}
	}

	public function getCommand()
	{
		return $this->command;
	}

	public function canResolve()
	{
		return $this->can_resolve;
	}
}