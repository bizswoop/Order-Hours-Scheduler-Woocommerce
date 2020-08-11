<?php
/**
 * Plugin Name: Store Hours Manager for WooCommerce
 * Plugin URI: http://www.bizswoop.com/wp/orderhours
 * Description: Create Custom Open & Close Store Schedules for Automatically Enabling & Disabling Customer Checkout Functionality for WooCommerce 
 * Version: 4.0.9
 * Text Domain: order-hours-scheduler-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 2.4.0
 * WC tested up to: 4.3.0
 * Author: BizSwoop a CPF Concepts, LLC Brand
 * Author URI: http://www.bizswoop.com
 */

namespace Zhours;
const ACTIVE = true;
const PLUGIN_ROOT = __DIR__;
const PLUGIN_ROOT_FILE = __FILE__;
const ASPECT_PREFIX = 'zh';

defined('ABSPATH') or die('No script kiddies please!');

spl_autoload_register(function ($name) {
	$name = explode('\\', $name);
	if($name[0] === __NAMESPACE__) {
		$name[0] = null;
	}
	$name = array_filter($name);

	$path = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $name) . '.php';
	if (file_exists($path)) {
		require_once $path;
	}
}, false);

new Setup();

