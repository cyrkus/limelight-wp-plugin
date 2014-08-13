<?php
/**
 * Limelight
 *
 * Sync Attendee and other data captured by wordpress with the Limelight API.
 *
 * @package   limelight
 * @author    7/Apps <ryan@7apps.com>
 * @license   GPL-2.0+
 * @link      http://www.7apps.com
 * @copyright 7-30-2014 7/Apps
 *
 * @wordpress-plugin
 * Plugin Name: Limelight
 * Plugin URI:  http://www.7apps.com
 * Description: Sync Attendee and other data captured by wordpress with the Limelight API.
 * Version:     1.0.0
 * Author:      7/Apps <ryan@7apps.com>
 * Author URI:  http://www.7apps.com
 * Text Domain: limelight-locale
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if (!defined("WPINC")) {
	die;
}

require_once(plugin_dir_path(__FILE__) . "inc/limelight.php");
require_once(plugin_dir_path(__FILE__) . "inc/limelight_model.php");
require_once(plugin_dir_path(__FILE__) . "inc/limelight_API.php");
require_once(plugin_dir_path(__FILE__) . "inc/limelight_GFFormList.php");

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook(__FILE__, array("Limelight", "activate"));
register_deactivation_hook(__FILE__, array("Limelight", "deactivate"));

Limelight::get_instance();
