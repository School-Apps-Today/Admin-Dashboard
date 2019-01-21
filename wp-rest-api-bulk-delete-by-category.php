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
 * Version:           2.0
 * Author:            Steven A. Zahm
 * Author URI:        https://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-rest-api-bulk-delete-by-category
 * Domain Path:       /languages
 */

if ( ! class_exists( 'WP_REST_API_BULK_Delete_By_Category' ) ) {

	final class WP_REST_API_BULK_Delete_By_Category {

		const VERSION = '2.0';

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

			add_action( 'rest_api_init', array( 'WP_REST_API_BULK_Delete_By_Category_REST_Controller', 'init' ) );

			add_shortcode( 'remote-bulk-delete-button', array( __CLASS__, 'shortcodeButton' ) );
			add_shortcode( 'remote-bulk-delete-auto-complete', array( __CLASS__, 'shortcodeAutoComplete' ) );

			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'registerJavaScripts' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'registerCSS' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueueCSS' ) );
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
				array( 'jquery', 'jquery-ui-autocomplete' ),
				self::VERSION,
				TRUE
			);

			wp_localize_script(
				'rbd',
				'wpApiSettings',
				array(
					'root'  => esc_url_raw( rest_url() ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
				)
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
		public static function registerCSS() {

			$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$url = WP_REST_API_BULK_Delete_By_Category()->getURL();

			$wp_scripts = wp_scripts();

			wp_enqueue_style(
				'jquery-ui-theme-smoothness',
				sprintf(
					'//ajax.googleapis.com/ajax/libs/jqueryui/%s/themes/smoothness/jquery-ui.css',
					// working for https as well now
					$wp_scripts->registered['jquery-ui-core']->ver
				),
				array(),
				$wp_scripts->registered['jquery-ui-core']->ver
			);

			wp_register_style(
				'rbd',
				"{$url}assets/css/public.css",
				array( 'jquery-ui-theme-smoothness' ),
				self::VERSION
			);
		}

		/**
		 * @since 1.0
		 */
		public static function enqueueCSS() {

			wp_enqueue_style( 'rbd' );
		}

		/**
		 * Renders an autocomplete input which will search a remote site by post title for matches. If the user selects
		 * a found post, they will have the option to delete the remote post.
		 * 
		 * @since 1.0
		 *
		 * @param array  $atts
		 * @param string $content
		 * @param string $tag
		 *
		 * @return string
		 */
		public static function shortcodeAutoComplete( $atts, $content = '', $tag = 'remote-bulk-delete-auto-complete' ) {

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

			$atts['category']     = wp_parse_id_list( $atts['category'] );
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

			if ( 0 < strlen( $atts['button-text'] ) ) {

				$text = $atts['button-text'];

			} else {

				if ( $atts['trash'] ) {

					$text = sprintf(
						'Trash selected post from site %1$s.',
						$atts['url']
					);

				} else {

					/** @noinspection SqlNoDataSourceInspection */
					$text = sprintf(
						'Delete selected post from site %1$s.',
						$atts['url']
					);
				}
			}

			$html .= '<div class="rbd-post-fieldset">';

			$html .= sprintf(
				'<input type="text" class="ui-autocomplete-input" placeholder="Search" data-url="%1$s" data-category="%2$s">',
				trailingslashit( $atts['url'] ),
				implode( ',', $atts['category'] )
			);

			$html .= sprintf(
				'<button class="rbd-post" data-url="%1$s" data-key="%2$s" data-confirm="%3$s" disabled>' . $text . '</button>',
				trailingslashit( $atts['url'] ),
				base64_encode( $atts['username'] . ':' . $atts['app-password'] ),
				$atts['confirm-text']
			);

			$html .= '</div>';

			return $html;
		}

		/**
		 * Shortcode which renders a button which can be configured to bulk delete all posts within the specified category.
		 *
		 * @since 1.0
		 *
		 * @param array  $atts
		 * @param string $content
		 * @param string $tag
		 *
		 * @return string
		 */
		public static function shortcodeButton( $atts, $content = '', $tag = 'remote-bulk-delete-button' ) {

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
				'<button class="rbd-posts-in-category" data-url="%1$s" data-key="%2$s" data-category="%3$d" data-confirm="%4$s">' . $text . '</button>',
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
