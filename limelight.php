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
 * Plugin Name:       Limelight
 * Plugin URI:        https://github.com/sevenapps/limelight-wp-plugin
 * Description:       Sync Attendee and other data captured by wordpress with the Limelight API.
 * Version:           1.2.4
 * Author:            7/Apps <ryan@7apps.com>
 * Author URI:        http://www.7apps.com
 * Text Domain:       limelight
 * Domain Path:       /lang
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/sevenapps/limelight-wp-plugin
 * GitHub Branch:     master
 */

// If this file is called directly, abort.
if (!defined("WPINC")) {
	die;
}

define('PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once(PLUGIN_DIR . "inc/limelight.php");
require_once(PLUGIN_DIR . "inc/limelight_model.php");
require_once(PLUGIN_DIR . "inc/limelight_API.php");
require_once(PLUGIN_DIR . "inc/limelight_GFFormList.php");

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook(__FILE__, array("Limelight", "activate"));
register_deactivation_hook(__FILE__, array("Limelight", "deactivate"));

Limelight::get_instance();
