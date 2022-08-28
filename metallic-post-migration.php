<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://metallic.io
 * @since             1.0.0
 * @package           postmigration
 *
 * @wordpress-plugin
 * Plugin Name:       Metallic Post Migration
 * Plugin URI:        https://metallic.io
 * Description:       This plugin provide functionality to migrate all the post data from one server to another server.
 * Version:           1.0.0
 * Author:            Metallic
 * Author URI:        https://metallic.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       metallic-post-migration
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'Metallic_Post_Migration_VERSION', '1.0.0' );

define( 'Metallic_Post_Migration_Prod_Link', get_option('global_production_link') );

define( 'Metallic_Post_Migration_IS_Staging', get_option('global_is_staging') );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-metallic-post-migration-activator.php
 */
function activate_Metallic_Post_Migration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-metallic-post-migration-activator.php';
	Metallic_Post_Migration_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-metallic-post-migration-deactivator.php
 */
function deactivate_Metallic_Post_Migration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-metallic-post-migration-deactivator.php';
	Metallic_Post_Migration_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_Metallic_Post_Migration' );
register_deactivation_hook( __FILE__, 'deactivate_Metallic_Post_Migration' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-metallic-post-migration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_Metallic_Post_Migration() {

	$plugin = new Metallic_Post_Migration();
	$plugin->run();

}
run_Metallic_Post_Migration();