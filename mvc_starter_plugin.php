<?php
/*
Plugin Name: MVC Starter WordPress Plugin
Plugin URI: http://github.com/kendru/mvc-starter-wp-plugin
Description: Starter plugin framework that enables developers to write MVC-style plugins
Version: 0.1
Author: Andrew Meredith
Author URI: http://www.andrewmeredith.info
*/

/**
 * Copyright (c) 2012 Andrew Meredith <andymeredith@gmail.com>. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

require 'vendor/autoload.php';

$app = new MVCStarterPlugin\Application();
$app->setRootDir(plugin_dir_path(__FILE__));
$app->setConfigDir(plugin_dir_path(__FILE__) . 'config/');
$app->setPublicDir(plugin_dir_path(__FILE__) . 'public/');
$app->setCacheDir(plugin_dir_path(__FILE__) . 'cache/');