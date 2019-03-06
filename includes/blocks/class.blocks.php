<?php

namespace School_Apps_Today;

//use cnURL as URL;

/**
 * Class Blocks
 *
 * @package School_Apps_Today
 */
class Blocks {

	/**
	 * @since 1.0
	 */
	public static function register() {

		if ( ! function_exists( 'register_block_type' ) ||
		     ! function_exists( 'wp_set_script_translations' ) // Required as the Gutenberg plugin does not have this function.
		) {

			return;
		}

		// Enqueue the editor assets for the blocks.
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueueEditorAssets' ) );

		// Enqueue the frontend block assets.
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueueAssets' ) );

		// Register Connections blocks category.
		add_filter( 'block_categories', array( __CLASS__, 'registerCategories' ), 10, 2 );

		// Register the editor blocks.
		add_action( 'init', 'School_Apps_Today\Blocks\Category_Datatable::register' );
	}

	/**
	 * Callback for the `enqueue_block_editor_assets` action.
	 *
	 * @since 1.0
	 */
	public static function enqueueEditorAssets() {

		// If SCRIPT_DEBUG is set and TRUE load the non-minified JS files, otherwise, load the minified files.
		//$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		$url  = SAT_Admin_Dashboard()->getURL();
		$path = SAT_Admin_Dashboard()->getPath();

		$jsDependencies = array(
			'lodash',
			'wp-plugins',
			'wp-element',
			'wp-edit-post',
			'wp-i18n',
			'wp-api-request',
			'wp-data',
			'wp-hooks',
			'wp-plugins',
			'wp-components',
			'wp-blocks',
			'wp-editor',
			'wp-compose',
		);

		wp_enqueue_script(
			'sat/admin-dashboard',
			"{$url}assets/dist/index.js",
			$jsDependencies,
			Admin_Dashboard::VERSION . '-' . filemtime( "{$path}assets/dist/index.js" ),
			TRUE
		);

		wp_set_script_translations( 'sat/admin-dashboard', 'sat/admin-dashboard' );
	}

	/**
	 * Callback for the `enqueue_block_assets` action.
	 *
	 * @since 1.0
	 */
	public static function enqueueAssets() {

		// If SCRIPT_DEBUG is set and TRUE load the non-minified JS files, otherwise, load the minified files.
		//$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		$url  = SAT_Admin_Dashboard()->getURL();
		$path = SAT_Admin_Dashboard()->getPath();

		wp_enqueue_style(
			'sat/admin-dashboard',
			"{$url}assets/dist/css/styles.css",
			array(),
			Admin_Dashboard::VERSION . '-' . filemtime( "{$path}assets/dist/css/styles.css" )
		);
	}

	/**
	 * Callback for the `block_categories` filter.
	 *
	 * Register the Connections category for the blocks.
	 *
	 * @since 1.0
	 *
	 * @param array    $categories Array of block categories.
	 * @param \WP_Post $post       Post being loaded.
	 *
	 * @return array
	 */
	public static function registerCategories( $categories, $post ) {

		$categories[] = array(
			'slug'  => 'school-apps-today',
			'title' => 'School Apps Today',
			'icon'  => NULL,
		);

		return $categories;
	}
}

