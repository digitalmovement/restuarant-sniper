<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://ianbryce.com
 * @since      1.0.0
 *
 * @package    Restaurant_Sniper
 * @subpackage Restaurant_Sniper/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Restaurant_Sniper
 * @subpackage Restaurant_Sniper/includes
 * @author     Ian Bryce <ian@digitalmovement.co.uk>
 */
class Restaurant_Sniper_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'restaurant-sniper',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
