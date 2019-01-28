<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_REST_Controller_PageLoader_Settings
 *
 * @link https://gist.github.com/soderlind/92593c6fb6d1c633fdb9
 */
class WP_REST_Controller_PageLoader_Settings extends WP_REST_Attachments_Controller {

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

		$files = $request->get_file_params();

		if ( ! empty( $files ) ) {

			$headers = $request->get_headers();
			$image   = $this->upload( $files, $headers );

			if ( is_wp_error( $image ) ) {

				return $image;
			}

			$this->createAttachment( $request, $image );

			$options['pageloader_custom_loading_image'] = $image['url'];
		}

		PageLoader_Settings::update( $options );

		$response = rest_ensure_response( PageLoader_Settings::get() );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Handles an upload via multipart/form-data ($_FILES).
	 *
	 * @see WP_REST_Attachments_Controller::upload_from_file()
	 *
	 * @since 2.0
	 *
	 * @param array $files Data from the `$_FILES` superglobal.
	 * @param array $headers HTTP headers from the request.
	 *
	 * @return array|WP_Error Data from wp_handle_upload().
	 */
	protected function upload( $files, $headers ) {

		if ( empty( $files ) ) {

			return new WP_Error( 'rest_upload_no_data', __( 'No data supplied.' ), array( 'status' => 400 ) );
		}

		// Verify hash, if given.
		if ( ! empty( $headers['content_md5'] ) ) {

			$content_md5 = array_shift( $headers['content_md5'] );
			$expected    = trim( $content_md5 );
			$actual      = md5_file( $files['file']['tmp_name'] );

			if ( $expected !== $actual ) {

				return new WP_Error(
					'rest_upload_hash_mismatch',
					__( 'Content hash did not match expected.' ),
					array( 'status' => 412 )
				);
			}
		}

		// Pass off to WP to handle the actual upload.
		$overrides = array(
			'test_form' => FALSE,
		);

		// Bypasses is_uploaded_file() when running unit tests.
		if ( defined( 'DIR_TESTDATA' ) && DIR_TESTDATA ) {
			$overrides['action'] = 'wp_handle_mock_upload';
		}

		$size_check = self::checkUploadFileSize( $files['file'] );

		if ( is_wp_error( $size_check ) ) {

			return $size_check;
		}

		/** Include admin function to get access to wp_handle_upload(). */
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$file = wp_handle_upload( $files['file'], $overrides );

		if ( isset( $file['error'] ) ) {

			return new WP_Error( 'rest_upload_unknown_error', $file['error'], array( 'status' => 500 ) );
		}

		return $file;
	}

	/**
	 * @see WP_REST_Attachments_Controller::create_item()
	 *
	 * @since 2.0
	 *
	 * @param WP_REST_Request $request
	 * @param array           $file
	 *
	 * @return bool|WP_Error Returns true on success, WP_Error on failure.
	 */
	protected function createAttachment( $request, $file ) {

		// Include image functions to get access to wp_read_image_metadata().
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// use image exif/iptc data for title and caption defaults if possible
		$image_meta = wp_read_image_metadata( $file['file'] );

		if ( ! empty( $image_meta ) ) {

			if ( empty( $request['title'] ) && trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
				$request['title'] = $image_meta['title'];
			}

			if ( empty( $request['caption'] ) && trim( $image_meta['caption'] ) ) {
				$request['caption'] = $image_meta['caption'];
			}
		}

		$attachment                 = $this->prepare_item_for_database( $request );
		$attachment->post_mime_type = $file['type'];
		$attachment->guid           = $file['url'];

		if ( empty( $attachment->post_title ) ) {

			$attachment->post_title = preg_replace( '/\.[^.]+$/', '', basename( $file['file'] ) );
		}

		// $post_parent is inherited from $attachment['post_parent'].
		$id = wp_insert_attachment( wp_slash( (array) $attachment ), $file['file'], 0, TRUE );

		if ( is_wp_error( $id ) ) {

			if ( 'db_update_error' === $id->get_error_code() ) {

				$id->add_data( array( 'status' => 500 ) );

			} else {

				$id->add_data( array( 'status' => 400 ) );
			}

			return $id;
		}

		$attachment = get_post( $id );

		/**
		 * Fires after a single attachment is created or updated via the REST API.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Post         $attachment Inserted or updated attachment
		 *                                    object.
		 * @param WP_REST_Request $request    The request sent to the API.
		 * @param bool            $creating   True when creating an attachment, false when updating.
		 */
		do_action( 'rest_insert_attachment', $attachment, $request, TRUE );

		// Include admin function to get access to wp_generate_attachment_metadata().
		require_once ABSPATH . 'wp-admin/includes/media.php';

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file['file'] ) );

		if ( isset( $request['alt_text'] ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
		}

		$fields_update = $this->update_additional_fields_for_object( $attachment, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		/**
		 * Fires after a single attachment is completely created or updated via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_Post         $attachment Inserted or updated attachment object.
		 * @param WP_REST_Request $request    Request object.
		 * @param bool            $creating   True when creating an attachment, false when updating.
		 */
		do_action( 'rest_after_insert_attachment', $attachment, $request, TRUE );

		return TRUE;
	}

	/**
	 * Determine if uploaded file exceeds space quota on multisite.
	 *
	 * Replicates check_upload_size().
	 *
	 * @see WP_REST_Attachments_Controller::check_upload_size()
	 *
	 * @since 2.0
	 *
	 * @param array $file $_FILES array for a given file.
	 *
	 * @return true|WP_Error True if can upload, error for errors.
	 */
	protected function checkUploadFileSize( $file ) {

		if ( ! is_multisite() ) {

			return TRUE;
		}

		if ( get_site_option( 'upload_space_check_disabled' ) ) {

			return TRUE;
		}

		$space_left = get_upload_space_available();

		$file_size = filesize( $file['tmp_name'] );

		if ( $space_left < $file_size ) {

			/* translators: %s: required disk space in kilobytes */
			return new WP_Error(
				'rest_upload_limited_space',
				sprintf(
					__( 'Not enough space to upload. %s KB needed.' ),
					number_format( ( $file_size - $space_left ) / KB_IN_BYTES )
				),
				array( 'status' => 400 )
			);
		}

		if ( $file_size > ( KB_IN_BYTES * get_site_option( 'fileupload_maxk', 1500 ) ) ) {

			/* translators: %s: maximum allowed file size in kilobytes */
			return new WP_Error(
				'rest_upload_file_too_big',
				sprintf(
					__( 'This file is too big. Files must be less than %s KB in size.' ),
					get_site_option( 'fileupload_maxk', 1500 )
				),
				array( 'status' => 400 )
			);
		}

		// Include admin function to get access to upload_is_user_over_quota().
		require_once ABSPATH . 'wp-admin/includes/ms.php';

		if ( upload_is_user_over_quota( FALSE ) ) {

			return new WP_Error(
				'rest_upload_user_quota_exceeded',
				__( 'You have used your space quota. Please delete files before uploading.' ),
				array( 'status' => 400 )
			);
		}

		return TRUE;
	}
}
