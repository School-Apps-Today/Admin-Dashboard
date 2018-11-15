<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_REST_API_BULK_Delete_By_Category_REST_Controller extends WP_REST_Controller {

	/**
	 * @since 1.0
	 */
	const VERSION = '1';

	/**
	 * @since 1.0
	 * @var string
	 */
	protected $namespace;

	/**
	 * @since 1.0
	 * @var string
	 */
	protected $base = 'delete-posts-in';

	/**
	 * @since 1.0
	 */
	public function __construct() {

		$this->namespace = 'rbd-api/v' . self::VERSION;
	}

	/**
	 * Callback for the `rest_api_init` action.
	 */
	public static function init() {

		$self = new self();
		$self->register();
	}

	/**
	 * @since 1.0
	 */
	public function register() {

		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'deletePosts' ),
					'permission_callback' => array( $this, 'deletePostsPermissionsCheck' ),
					'args'                => array(
						'id'    => array(
							'required'          => TRUE,
							'validate_callback' => array( $this, 'isNumeric' ),
							'sanitize_callback' => array( $this, 'sanitizeID' ),
						),
						'force' => array(
							'default'           => FALSE,
							//'validate_callback' => array( $this, 'isBool' ),
							'sanitize_callback' => array( $this, 'toBoolean' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Delete one item from the collection.
	 *
	 * @since 1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Request
	 */
	public function deletePosts( $request ) {

		//error_log( json_encode( array( $request['id'], $request['force'] ), 128 ) );

		if ( is_numeric( $request['id'] ) && 0 < $request['id'] ) {

			$count   = 0;
			$options = array( 'category__in' => $request['id'] );

			$posts = $this->queryPosts( $options );

			if ( $posts ) {

				$count = $this->deletePostsByID( $posts, $request['force'] );
			}

			if ( $count ) {

				$response = $this->prepare_item_for_response( $posts, $request );

				return $response;
			}
		}

		return $this->prepare_item_for_response( array(), $request );
	}

	/**
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function deletePostsPermissionsCheck( $request ) {

		return current_user_can( 'delete_posts' );
	}

	/**
	 * @since 1.0
	 *
	 * @param mixed           $item
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {

		$response = rest_ensure_response( $item );

		return $response;
	}

	/**
	 * @since 1.0
	 *
	 * @param $options
	 *
	 * @return array
	 */
	private function queryPosts( $options ) {

		$defaults = array(
			'post_type'              => 'post',
			'cache_results'          => FALSE, // don't cache results.
			'update_post_meta_cache' => FALSE, // No need to fetch post meta fields.
			'update_post_term_cache' => FALSE, // No need to fetch taxonomy fields.
			'no_found_rows'          => TRUE,  // No need for pagination.
			'fields'                 => 'ids', // retrieve only ids.
		);

		$options = wp_parse_args( $options, $defaults );

		$wp_query = new WP_Query();

		$posts = $wp_query->query( $options );

		return $posts;
	}

	/**
	 * Delete posts by ids.
	 *
	 * @since 1.0
	 *
	 * @param int[] $postIDs     List of post ids to delete.
	 * @param bool  $forceDelete True to force delete posts, False otherwise.
	 *
	 * @return int Number of posts deleted.
	 */
	private function deletePostsByID( $postIDs, $forceDelete ) {

		foreach ( $postIDs as $post_id ) {

			// `$force_delete` parameter to `wp_delete_post` won't work for custom post types.
			// See https://core.trac.wordpress.org/ticket/43672
			if ( $forceDelete ) {

				wp_delete_post( $post_id, TRUE );

			} else {

				wp_trash_post( $post_id );
			}
		}

		return count( $postIDs );
	}

	/**
	 * @since 1.0
	 *
	 * @param $param
	 * @param $request
	 * @param $key
	 *
	 * @return bool
	 */
	public function isNumeric( $param, $request, $key ) {

		return is_numeric( $param );
	}

	/**
	 * @since 1.0
	 *
	 * @param $param
	 *
	 * @return int
	 */
	public function sanitizeID( $param ) {

		 return absint( $param );
	}

	/**
	 * Converts the following strings: yes/no; true/false and 0/1 to boolean values.
	 * If the supplied string does not match one of those values the method will return NULL.
	 *
	 * @since 1.0
	 *
	 * @param string|int|bool $param
	 *
	 * @return bool
	 */
	public static function toBoolean( $param ) {

		// Already a bool, return it.
		if ( is_bool( $param ) ) return $param;

		$param = filter_var( strtolower( $param ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

		if ( is_null( $param ) ) {

			$param = FALSE;
		}

		return $param;
	}
}
