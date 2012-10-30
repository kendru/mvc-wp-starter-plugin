<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Common;

use MVCStarterPlugin\Lib\AppRegistry;
use MVCStarterPlugin\Lib\SessionRegistry;
use MVCStarterPlugin\Lib\RequestRegistry;

/**
 * Base Controller
 * 
 * Defines common behaviour for controllers
 */
abstract class Controller {
	const MSG_ERROR = -1;
	const MSG_INFO = 0;
	const MSG_SUCCESS = 1;
	
	protected $layout;
	protected $renderer;
	protected $registry_app;
	protected $registry_session;
	protected $registry_request;
	
	public function __construct() {
		$this->registry_app = AppRegistry::instance();
	}
	
	protected function requireAuthentication() {
		if ($this->_session->isLoggedIn()) {
			return true;
		} else {
			$this->renderHome(self::MSG_ERROR, "You must be logged in to access that.");
		}
	}
	
	public function renderHome($status_code, $status_message) {
		$yield = <<<HERE
		<div class="alert">
			$status_message
		</div>
HERE;
		$this->render($yield);
	}
	
	public function getPostVar($var) {
		$val = $_POST[$var];
		if (get_magic_quotes_gpc())
			$val = stripslashes($val);
		return $val;
	}

	/**
	 * Delegates action calls to the appropriate method.
	 *
	 * Detects whether user is on the dashboard or not and attempts to call
	 * the 'admin_' method in the dashboard and the 'front_' method on the
	 * front end, delgating to a common method if the specific method cannot
	 * be called.
	 * 
	 * @param String $action The name of the action to call on this controller
	 * @return void
	 */
	public function __call($action, $args)
	{
		$admin_action = "admin_$action";
		$front_action = "front_$action";
		$general_action = "do_$action";

		if (function_exists('is_admin')
			&& is_admin()
			&& method_exists($this, $admin_action)
			&& is_callable(array($this, $admin_action))
		) {
			$this->$admin_action();
		} elseif (function_exists('is_admin')
				  && !is_admin()
				  && method_exists($this, $front_action)
				  && is_callable(array($this, $front_action))
		) {
			$this->$front_action();
		} elseif (method_exists($this, $general_action)
				  && is_callable(array($this, $general_action))
		) {
			$this->$general_action();
		} else {
			throw new \Exception("Invalid action supplied for Controller, " . get_class());	
		}
	}
	
	protected function render($yield) {
		$this->updateLayout(array('yield' => $yield));
		print $this->_layout;
	}
	private function generateLayout($args = array()) {
		$this->_layout = file_get_contents('../layout.html');
		$this->updateLayout($args);
	}
	
	private function updateLayout($args) {
		foreach ($args as $tag => $contents) {
			$this->_layout = str_replace("{{$tag}}", $contents, $this->_layout);
		}
	}
}