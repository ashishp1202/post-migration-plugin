<?php

/**
 * Fired during plugin activation
 *
 * @link			 https://metallic.io
 * @since			1.0.0
 *
 * @package		Metallic_Post_Migration
 * @subpackage Metallic_Post_Migration/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since			1.0.0
 * @package		Metallic_Post_Migration
 * @subpackage Metallic_Post_Migration/includes
 * @author		 Metallic <>
 */
class Metallic_Post_Migration_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since		1.0.0
	 */
	public static function activate() {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	}

}
