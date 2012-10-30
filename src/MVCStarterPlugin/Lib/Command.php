<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib;

class Command
{
	private $controller;
	private $action;

	public function __construct($controller = null, $action = null)
	{
		$this->controller = $this->toClassName($controller);
		$this->action = $this->toMethodName($action);
	}

	public function execute()
	{
		if (!$this->controller
			|| !$this->action
			) {
			return;
		}

		$classname = '\\MVCStarterPlugin\\Controllers\\' . $this->controller;
		$action = $this->action;

		if (!class_exists($classname)) {
			throw new \DomainException('Controller, "' . $this->controller . '", not found');
		}

		$instance = new $classname();
		$instance->$action();
		exit(0);
	}

	private function toClassName($name)
	{
		$words = ucwords(preg_replace('/([^a-zA-Z0-9])/', ' ', $name));
		return str_replace(' ', '', $words);
	}

	private function toMethodName($name)
	{
		$words = explode(' ', strtolower(preg_replace('/([^a-zA-Z0-9])/', ' ', $name)));
		if (count($words) > 1) {
			for ($i = 1; $i < count($words); $i++) { 
				$words[$i] = ucfirst($words[$i]);
			}
			$name = implode('', $words);
		} else {
			$name = $words[0];
		}

		return $name;
	}

	public function getController()
	{
		return $this->controller;
	}

	public function setController($controller)
	{
		$this->controller = $controller;
	}

	public function getAction()
	{
		return $this->action;
	}

	public function setAction($action)
	{
		$this->action = $action;
	}
}