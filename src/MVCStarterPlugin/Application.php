<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin;

use MVCStarterPlugin\Lib\AppRegistry;
use MVCStarterPlugin\Lib\SessionRegistry;
use MVCStarterPlugin\Lib\RequestRegistry;
use MVCStarterPlugin\Lib\Router;

/**
 * Application entry-point
 * 
 * Initializes router and registers; hooks into WordPress.
 */
class Application
{
	private $root_dir;
	private $config_dir;
	private $public_dir;
	private $cache_dir;
	private $router;
	private $register_session;
	private $register_app;
	private $register_request;
	private $wp_wrapper;

	public function __construct($wp_wrapper = null)
	{
		if (!$wp_wrapper
			&& !function_exists('add_action')) {
			throw new \Exception("Neither WordPress nor envorinment loaded");
		}

		if ($wp_wrapper) {
			$wp_wrapper->add_action('init', array($this, 'init'));
			$wp_wrapper->add_action('template_redirect', array($this, 'doRouting'));
		} else {
			add_action('init', array($this, 'init'));
			add_action('template_redirect', array($this, 'doRouting'));
		}
	}

	public function init()
	{
		// Register routers
		$this->register_app 	= AppRegistry::instance($this); // Application registry needs access to application config settings
		$this->register_session = SessionRegistry::instance();
		$this->register_request = RequestRegistry::instance();
	}

	public function doRouting() {
		$this->router = new Router($this);
		if ($this->router->canResolve()) {
			$this->router->getCommand()->execute();
		}
	}

	public function getName()
	{
		return strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $this->register_app->get('name')));
	}

// BEGIN Application path settings {{{
	public function getRootDir()
	{
		return $this->root_dir;
	}

	public function setRootDir($root_dir)
	{
		$this->root_dir = $root_dir;
	}

	public function getConfigDir()
	{
		return $this->config_dir;
	}

	public function setConfigDir($config_dir)
	{
		$this->config_dir = $config_dir;
	}

	public function getPublicDir()
	{
		return $this->public_dir;
	}

	public function setPublicDir($public_dir)
	{
		$this->public_dir = $public_dir;
	}

	public function getCacheDir()
	{
		return $this->cache_dir;
	}

	public function setCacheDir($cache_dir)
	{
		$this->cache_dir = $cache_dir;
	}
// END Application path settings }}}
}