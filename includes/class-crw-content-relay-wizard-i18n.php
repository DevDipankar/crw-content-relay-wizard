<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://domain.com
 * @since      1.0.0
 *
 * @package    Crw_Content_Relay_Wizard
 * @subpackage Crw_Content_Relay_Wizard/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Crw_Content_Relay_Wizard
 * @subpackage Crw_Content_Relay_Wizard/includes
 * @author     DIpankar Pal <dipankar.pal@capitalnumbers.com>
 */
class Crw_Content_Relay_Wizard_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'crw-content-relay-wizard',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
