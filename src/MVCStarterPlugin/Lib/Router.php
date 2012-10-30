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
	private $can_resolve = false;
	private $command;

	public function __construct($application, $wp_wrapper = null)
	{

		$handle = $application->getName();
		
		if (!$wp_wrapper) {
			$wp_wrapper = new WPWrapper();
		}

		$handle_var = $wp_wrapper->get_query_var($handle);
		$ctrl = $wp_wrapper->get_query_var('ctrl');
		$cmd = $wp_wrapper->get_query_var('cmd');

		$app_reg = AppRegistry::instance($wp_wrapper);

		// If WP query variables present
		if ($handle_var
			&& $handle_var === "true"
			&& $ctrl
			&& $cmd
			) {
			
			$this->can_resolve = true;
			$this->command = new Command($ctrl, $cmd);
		// If default values are set (usually from first landing on the admin page)
		} elseif ($app_reg->get('default_controller')) {

			$ctrl = $app_reg->get('default_controller');
			$cmd = $app_reg->get('default_command');
			$this->can_resolve = true;
			$this->command = new Command($ctrl, $cmd);
		// Otherwise...
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