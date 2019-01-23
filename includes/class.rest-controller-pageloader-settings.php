<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_REST_Controller_PageLoader_Settings extends WP_REST_Controller {

	/**
	 * @since 2.0
	 */
	const VERSION = '1';

	/**
	 * @since 2.0
	 * @var string
	 */
	protected $namespace;

	/**
	 * @since 2.0
	 * @var string
	 */
	protected $base = 'settings';

	/**
	 * @since 12.0
	 */
	public function __construct() {

		$this->namespace = 'pageloader-api/v' . self::VERSION;
	}

	/**
	 * Callback for the `rest_api_init` action.
	 */
	public static function init() {

		$self = new self();
		$self->register();
	}

	/**
	 * Register routes.
	 *
	 * @since 2.0
	 */
	public function register() {

		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'              => 'POST',
				'callback'             => array( $this, 'update' ),
				'args'                 => array(
					'pageloader_custom_loading_image'        => array(
						'type'              => 'string',
						'required'          => FALSE,
						'sanitize_callback' => 'esc_url_raw',
					),
					'bonfire_pageloader_custom_loading_text' => array(
						'type'              => 'string',
						'required'          => FALSE,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permissions_callback' => array( $this, 'permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'              => 'GET',
				'callback'             => array( $this, 'get' ),
				'args'                 => array(),
				'permissions_callback' => array( $this, 'permissions' ),
			)
		);
	}

	/**
	 * Check request permissions.
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	public function permissions() {

		return current_user_can( 'manage_options' );
	}

	/**
	 * Get settings.
	 *
	 * @since 2.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function get( WP_REST_Request $request ) {

		return rest_ensure_response( PageLoader_Settings::get() );
	}

	/**
	 * Update settings.
	 *
	 * @since 2.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {

		$options = wp_parse_args( $request->get_params(), PageLoader_Settings::get() );

		PageLoader_Settings::update( $options );

		$response = rest_ensure_response( PageLoader_Settings::get() );
		$response->set_status( 201 );

		return $response;
	}
}
