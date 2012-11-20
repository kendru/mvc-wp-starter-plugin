<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Common;

use MVCStarterPlugin\Lib\AppRegistry;
use MVCStarterPlugin\Lib\SessionRegistry;
use MVCStarterPlugin\Lib\RequestRegistry;
use MVCStarterPlugin\Lib\Util\Inflector;

/**
 * Base Controller
 * 
 * Defines common behaviour for controllers
 */
abstract class Controller {
	const MSG_ERROR = 'error';
	const MSG_INFO = 'info';
	const MSG_SUCCESS = 'success';
	
	protected $layout;
	protected $renderer;
	private $layout_renderer;

	private $template_dir;

	protected $registry_app;
	protected $registry_session;
	protected $registry_request;
	
	public function __construct() {
		$this->registry_app 	= AppRegistry::instance();
		$this->registry_session = SessionRegistry::instance();
		$this->registry_request = RequestRegistry::instance();

		$this->template_dir = $this->registry_app->get('template_dir');

		$twigloader = new \Twig_Loader_Filesystem($this->template_dir);
		$this->renderer = new \Twig_Environment($twigloader, array(
    		'cache' => $this->registry_app->get('cache_dir'),
    		'auto_reload' => true,
    		'autoescape' => true
		));

		$this->layout_renderer = new \Twig_Environment($twigloader, array(
    		'cache' => $this->registry_app->get('cache_dir'),
    		'auto_reload' => true,
    		'autoescape' => false
		));

		// Default to the "Front" layout - this can be overridden per controller (in the constructor) or per-action
		$this->useLayout('front');
	}
	
	protected function requireAuthentication() {
		if ($user = $this->registry_session->getLoggedInUser()) {
			return $user;
		} else {
			$this->addFlash("You must be logged in to access that.", self::MSG_ERROR);
			return false;
		}
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
			throw new \Exception("Invalid action, \"$action\", supplied for Controller, " . get_class());	
		}

		return is_admin() ? 'admin' : 'front';
	}
	
	protected function addFlash($message, $status = self::MSG_INFO)
	{
		$flash = $this->registry_request->get('flash');
		$flash[$status][] = $message;
		$this->registry_request->set('flash', $flash);
	}

	protected function getFlash($status = null)
	{
		$flash = $this->registry_request->get('flash');
		return ($status) ? $flash[$status] : $flash;
	}

	/**
	 * Renders the associated view
	 * 
	 * This method is designed to be called from a child class to render an
	 * view from an HTML template. For other formats, @see renderAs.
	 * 
	 * @param array $args the variables to use to populate the view
	 * @param string $view optional name of the view file to load - defaults to the action name.
	 * May be specified as either a single view name, e.g. 'index', or as a controller/view pair,
	 * e.g. 'Persons/index'.
	 */
	protected function render($args = array(), $view = null)
	{
		// Provide default controller and action from backtrace
		list(, $caller) = debug_backtrace(false);
		
		// If called from $this->renderAs, get the next caller up the chain.
		if ($caller['function'] === 'renderAs') {
			list(,, $caller) = debug_backtrace(false);
		}

		$from_controller = isset($caller['class']) ? Inflector::denamespace($caller['class']) : null;
		$from_action = $caller['function'];

		// Get controller and action for template from the $view string, if supplied
		if ($view && is_string($view)) {

			if (strpos($view, '/') === false) { // Only action name was supplied
			
				$controller = $from_controller;
				$action = $view;
			} else {
			
				list($controller, $action) = explode('/', $view);
				$controller = ucfirst($controller);
			}
		// Otherwise, use the calling controller/action to get view
		} else {

			$controller = $from_controller;
			$action = $from_action;
		}

		// Strip the do_ from the action name, if present
		if (strpos($action, 'do_') === 0) {
			$from_action = substr($action, 3);
		}

		// Default to common view if admin or front view not found
		if (strpos($action, 'admin_') === 0
			|| strpos($action, 'front_') === 0
			) {
			if (!is_readable("{$this->template_dir}{$controller}/{$action}.html")) {
				// Regex used to match so that we can easily add more action prefixes if needed
				$action = preg_replace('/^[\w]+_([\w]+)$/', '$1', $action);
			}
		}

		$this->doRender($controller, $action, $args);
	}

	/**
	 * Renders response in a variety of formats
	 */
	protected function renderAs($format, $args)
	{
		switch ($format) {
			case 'json':
				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header('Content-type: application/json');
				echo json_encode($args);
				break;
			
			default:
				throw new \InvalidArgumentException("Invalid response format given");
				
				break;
		}
	}

	protected function useLayout($layout)
	{
		if ($layout) {
			$this->layout = $this->layout_renderer->loadTemplate("Layout/$layout.html");
		} else {
			$this->layout = null;
		}
	}

	/**
	 * Performs the rendering of a template
	 * 
	 * @param string $directory The directory, relative to the main template directory, to look for template
	 * @param string $template The template file to load (sans ".html" file extension)
	 * @param array $args The associative array or arguments to use to populate the template
	 */
	private function doRender($directory, $template, $args)
	{
		if (!$this->layout) {
			$strloader = new \Twig_Loader_String();
			$local_renderer = new \Twig_Environment($strloader,array(
				'auto_reload' => false,
				'autoescape' => false
			));
			$this->layout = $local_renderer->loadTemplate('{{ yield }}');
		}

		$template_file = "{$directory}/{$template}.html";

		if (!is_readable($this->template_dir . $template_file)) {
			throw new \Exception("Template file, $template_file, does not exist");
		}

		$inner = $this->renderer->render($template_file, $args);

		echo $this->layout->render(array(
			'yield' => $inner,
			'wp' => new \MVCStarterPlugin\Lib\WPWrapper(),
			'app' => $this->registry_app,
			'flash' => $this->getFlash()
		));
	}
}