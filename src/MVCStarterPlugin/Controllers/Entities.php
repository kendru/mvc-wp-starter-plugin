<?php

namespace MVCStarterPlugin\Controllers;

use \MVCStarterPlugin\Lib\Common\Controller;

class Entities extends Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->useLayout('front');
	}

	public function front_index()
	{
		$this->render(array('greeting' => "Hello, user!"));
	}
	public function admin_index()
	{
		$user = $this->requireAuthentication();
		$this->useLayout('admin');
		$this->render(array('user' => $user));
	}
}