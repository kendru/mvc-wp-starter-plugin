<?php

namespace MVCStarterPlugin\Controllers;

use \MVCStarterPlugin\Lib\Common\Controller;

class Entities extends Controller
{
	public function front_show()
	{
		get_header();
		echo "Hooray! We made it to the front!";
		get_footer();
	}
	public function admin_show()
	{
		echo "Hooray! We made it to the admin!";
		exit(1);
	}
}