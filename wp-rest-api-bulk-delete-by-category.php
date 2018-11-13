<?php
/**
 * @package   WP REST API - Bulk Delete by Category
 * @category  plugin
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      https://connections-pro.com
 * @copyright 2018 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       WP REST API - Bulk Delete by Category
 * Plugin URI:        https://connections-pro.com/
 * Description:       Utilizes the WP REST API to bulk delete posts by category from a remote site.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        https://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-rest-api-bulk-delete-by-category
 * Domain Path:       /languages
 */

if ( ! class_exists( 'WP_REST_API_BULK_Delete_By_Category' ) ) {

	final class WP_REST_API_BULK_Delete_By_Category {

		const VERSION = '1.0';

		/**
		 * @var WP_REST_API_BULK_Delete_By_Category Stores the instance of this class.
		 *
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * @var string The absolute path this this file.
		 *
		 * @since 1.0
		 */
		private $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @since 1.0
		 */
		private $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @since 1.0
		 */
		private $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @since 1.0
		 */
		private $basename = '';

		/**
		 * A dummy constructor to prevent the class from being loaded more than once.
		 *
		 * @since 1.0
		 */
		public function __construct() { /* Do nothing here */ }

		/**
		 * The main Connection Form plugin instance.
		 *
		 * @since 1.0
		 *
		 * @return self
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {

				self::$instance = $self = new self;

				$self->file     = __FILE__;
				$self->url      = plugin_dir_url( $self->file );
				$self->path     = plugin_dir_path( $self->file );
				$self->basename = plugin_basename( $self->file );

				$self->includes();
				$self->hooks();
				$self->registerCSS();
			}

			return self::$instance;
		}

		private function includes() {

			require_once( 'includes/class.rest-controller.php' );
		}

		/**
		 * @since 1.0
		 */
		private function hooks() {

			add_shortcode( 'remote-bulk-delete-button', array( __CLASS__, 'shortcode' ) );
			add_action( 'rest_api_init', array( 'WP_REST_API_BULK_Delete_By_Category_REST_Controller', 'init' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'registerJavaScripts' ) );
		}

		/**
		 * @since 1.0
		 */
		public function getURL() {

			return $this->url;
		}

		/**
		 * @since 1.0
		 */
		public static function registerJavaScripts() {

			$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$url = WP_REST_API_BULK_Delete_By_Category()->getURL();
			$url = "{$url}assets/js/public.js";

			wp_register_script(
				'rbd',
				$url,
				array( 'jquery' ),
				self::VERSION,
				TRUE
			);
		}

		/**
		 * @since 1.0
		 */
		public static function enqueueJS() {
		}

		/**
		 * @since 1.0
		 */
		private function registerCSS() {

			$min    = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		}

		/**
		 * @since 1.0
		 */
		public static function enqueueCSS() {
		}

		/**
		 * @since 1.0
		 *
		 * @param array  $atts
		 * @param string $content
		 * @param string $tag
		 *
		 * @return string
		 */
		public static function shortcode( $atts, $content = '', $tag = 'remote-bulk-delete-button' ) {

			$html = '';

			wp_enqueue_script( 'rbd' );

			$defaults = array(
				'url'           => '',
				'username'      => '',
				'app-password'  => '',
				'category'      => NULL,
				'trash'         => TRUE,
				'button-text'   => '',
				'confirm-text'  => __( 'Are you Sure? This action cannot be undone.', 'wp-rest-api-bulk-delete-by-category' ),
				'require-login' => TRUE,
			);

			$atts = shortcode_atts( $defaults, $atts, $tag );

			$atts['category']     = absint( $atts['category'] );
			$atts['url']          = filter_var( $atts['url'], FILTER_SANITIZE_URL );
			$atts['confirm-text'] = sanitize_text_field( $atts['confirm-text'] );

			self::toBoolean( $atts['trash'] );
			self::toBoolean( $atts['require-login'] );

			if ( ! is_user_logged_in() && $atts['require-login'] ) {

				return $html;
			}

			if ( 0 > strlen( $atts['url'] ) ) {

				return '<p>' . __( 'Remote site URL must be provided', 'wp-rest-api-bulk-delete-by-category' ) . '</p>';
			}

			if ( FALSE === filter_var( $atts['url'], FILTER_VALIDATE_URL ) ) {

				return '<p>' . __( 'A valid URL must be provided', 'wp-rest-api-bulk-delete-by-category' ) . '</p>';
			}

			if ( 0 >= strlen( $atts['username'] ) ) {

				return '<p>' . __( 'A username  must be provided', 'wp-rest-api-bulk-delete-by-category' ) . '</p>';
			}

			if ( 0 >= strlen( $atts['app-password'] ) ) {

				return '<p>' . sprintf( __( 'An <a href="%s">Application Password</a> must be provided', 'wp-rest-api-bulk-delete-by-category' ), 'https://wordpress.org/plugins/application-passwords/' ) . '</p>';
			}

			if ( FALSE === filter_var( $atts['category'],FILTER_VALIDATE_INT ) || 0 >= $atts['category'] ) {

				return '<p>' . __( 'A category ID must be provided', 'wp-rest-api-bulk-delete-by-category' ) . '</p>';
			}

			if ( 0 < strlen( $atts['button-text'] ) ) {

				$text = $atts['button-text'];

			} else {

				if ( $atts['trash'] ) {

					$text = sprintf(
						'Trash posts in category %2$d from site %1$s.',
						$atts['url'],
						$atts['category']
					);

				} else {

					/** @noinspection SqlNoDataSourceInspection */
					$text = sprintf(
						'Delete posts in category %2$d from site %1$s.',
						$atts['url'],
						$atts['category']
					);
				}
			}

			$html = sprintf(
				'<button class="rbd" data-url="%1$s" data-key="%2$s" data-category="%3$d" data-confirm="%4$s">' . $text . '</button>',
				trailingslashit( $atts['url'] ),
				base64_encode( $atts['username'] . ':' . $atts['app-password'] ),
				$atts['category'],
				$atts['confirm-text']
			);

			return $html;
		}

		/**
		 * Converts the following strings: yes/no; true/false and 0/1 to boolean values.
		 * If the supplied string does not match one of those values the method will return NULL.
		 *
		 * @since 1.0
		 *
		 * @param string|int|bool $value
		 *
		 * @return bool
		 */
		public static function toBoolean( &$value ) {

			// Already a bool, return it.
			if ( is_bool( $value ) ) return $value;

			$value = filter_var( strtolower( $value ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

			if ( is_null( $value ) ) {

				$value = FALSE;
			}

			return $value;
		}
	}

	/**
	 * @since 1.0
	 *
	 * @return WP_REST_API_BULK_Delete_By_Category
	 */
	function WP_REST_API_BULK_Delete_By_Category() {

		return WP_REST_API_BULK_Delete_By_Category::instance();
	}

	WP_REST_API_BULK_Delete_By_Category();
}
