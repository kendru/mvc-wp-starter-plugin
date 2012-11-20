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
	private $template_dir;
	private $uploads_dir;
	private $router;
	private $register_session;
	private $register_app;
	private $register_request;
	private $wp_wrapper;

	public function __construct($wp_wrapper = null)
	{
		if (!$wp_wrapper) {
			$wp_wrapper = new Lib\WPWrapper();
		}
		$this->wp_wrapper = $wp_wrapper;

		// Register routers
		$this->register_app 	= AppRegistry::instance();
		$this->register_session = SessionRegistry::instance();
		$this->register_request = RequestRegistry::instance();

		$wp_wrapper->register_activation_hook(dirname(dirname(__DIR__)) . '/plugin.php', array($this, 'installPlugin'));
		$wp_wrapper->add_action('init', array($this, 'init'), 10);
		$wp_wrapper->add_action('init', array($this, 'configureRewriting'), 20);
		$wp_wrapper->add_action('query_vars', array($this, 'addQueryVars'));
		$wp_wrapper->add_action('template_redirect', array($this, 'doRouting'));
		$wp_wrapper->add_action('admin_menu', array($this, 'createAdminInterface'));
	}

	public function installPlugin()
	{
		$this->register_app->initialize($this->getConfigDir() . 'app.yaml', true);
		if ($this->root_dir) {
			$this->register_app->set('root_dir', $this->root_dir);
		}
		if ($this->config_dir) {
			$this->register_app->set('config_dir', $this->config_dir);
		}
		if ($this->cache_dir) {
			$this->register_app->set('cache_dir', $this->cache_dir);
		}
		if ($this->public_dir) {
			$this->register_app->set('public_dir', $this->public_dir);
		}
		if ($this->template_dir) {
			$this->register_app->set('template_dir', $this->template_dir);
		}
		if ($this->uploads_dir) {
			$this->register_app->set('uploads_dir', $this->uploads_dir);
		}

		$this->register_app->save();
	}

	public function init()
	{
		if ($this->wp_wrapper->is_user_logged_in()) {
			$this->register_session->setLoggedInUser($this->wp_wrapper->wp_get_current_user());
		}
	}

	public function doRouting() {
		$this->router = new Router($this);
		if ($this->router->canResolve()) {
			$this->router->getCommand()->execute();
		}
	}

	public function configureRewriting()
	{
		$basename = $this->getName();
		add_rewrite_rule("^$basename/([^/]*)/([^/]*)/?", 'index.php?' . $basename . '=true&ctrl=$matches[1]&cmd=$matches[2]', 'top');
	}

	public function addQueryVars($vars)
	{
		$basename = $this->getName();
		$vars[] = $basename;
		$vars[] = 'ctrl';
		$vars[] = 'cmd';
		return $vars;
	}

	public function createAdminInterface()
	{
		add_menu_page($this->register_app->get('name'),
					  $this->register_app->get('name'),
					  'manage_options',
					  $this->getName(),
					  array($this, 'doRouting'),
					  dirname(dirname(plugin_dir_url(__FILE__))) . '/public/img/menu_icon.png',
					  $position = 10);
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

	public function getTemplateDir()
	{
		return $this->template_dir;
	}

	public function setTemplateDir($template_dir)
	{
		$this->template_dir = $template_dir;
	}

	public function getUploadsDir()
	{
		return $this->uploads_dir;
	}

	public function setUploadsDir($uploads_dir)
	{
		$this->uploads_dir = $uploads_dir;
	}
// END Application path settings }}}

}