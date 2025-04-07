<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://domain.com
 * @since             1.0.0
 * @package           Crw_Content_Relay_Wizard
 *
 * @wordpress-plugin
 * Plugin Name:       Content Relay Wizard (CRW)
 * Plugin URI:        https://domain.com
 * Description:       Effortlessly Move Pages/Posts Upon Source Approval to Destination Platform.
 * Version:           1.0.0
 * Author:            DIpankar Pal
 * Author URI:        https://domain.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       crw-content-relay-wizard
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
define( 'CRW_CONTENT_RELAY_WIZARD_VERSION', '1.0.0' );
define( 'CRW_CONTENT_RELAY_WIZARD_PATH',  plugin_dir_path( __FILE__ ) );
define( 'CRW_CONTENT_RELAY_WIZARD_BASENAME',  plugin_basename( __FILE__ ) );


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-crw-content-relay-wizard-activator.php
 */
function activate_crw_content_relay_wizard() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-crw-content-relay-wizard-activator.php';
	Crw_Content_Relay_Wizard_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-crw-content-relay-wizard-deactivator.php
 */
function deactivate_crw_content_relay_wizard() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-crw-content-relay-wizard-deactivator.php';
	Crw_Content_Relay_Wizard_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_crw_content_relay_wizard' );
register_deactivation_hook( __FILE__, 'deactivate_crw_content_relay_wizard' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-crw-content-relay-wizard.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */


$plugin = new Crw_Content_Relay_Wizard();
$plugin->run();

$crw_init = $plugin->crw_init();


/**
 * require other files
 */
require plugin_dir_path( __FILE__ ) . 'includes/custom-functions.php';
require plugin_dir_path( __FILE__ ) . 'admin/admin-functions.php';


