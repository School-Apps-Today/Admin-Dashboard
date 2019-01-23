<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PageLoader_Settings {

	/**
	 * Default options.
	 *
	 * @since 2.0
	 *
	 * @var array
	 */
	protected static $defaults = array(
		'pageloader_custom_loading_image'        => '',
		'bonfire_pageloader_custom_loading_text' => '',
	);

	/**
	 * Get options.
	 *
	 * @return array
	 */
	public static function get() {

		$options = get_theme_mods();

		if ( ! is_array( $options ) || empty( $options ) ) {

			return self::$defaults;
		}

		return shortcode_atts( self::$defaults, $options );
	}

	/**
	 * Save options.
	 *
	 * @since 2.0
	 *
	 * @param array $options
	 */
	public static function update( array $options ) {

		$options = wp_parse_args( $options, get_theme_mods() );
		$theme   = get_option( 'stylesheet' );

		update_option( "theme_mods_{$theme}", $options );
	}

}
