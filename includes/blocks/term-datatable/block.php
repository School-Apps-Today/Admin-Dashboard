<?php

namespace School_Apps_Today\Blocks;

/**
 * Class Category_Datatable
 *
 * @package School_Apps_Today\Blocks
 * @since   1.0
 */
class Category_Datatable {

	/**
	 * Callback for the `init` action.
	 *
	 * Register the test block.
	 *
	 * @since 1.0
	 */
	public static function register() {

		register_block_type(
			'school-apps-today/category-datatable',
			array(
				// When displaying the block using ServerSideRender the attributes need to be defined
				// otherwise the REST API will reject the block request with a server response code 400 Bad Request
				// and display the "Error loading block: Invalid parameter(s): attributes" message.
				'attributes' => array(),
				// Not needed since script is enqueued in Connections_Directory\Blocks\enqueueEditorAssets()
				//'editor_script'   => '', // Registered script handle. Enqueued only on the editor page.
				// Not needed since styles are enqueued in Connections_Directory\Blocks\enqueueEditorAssets()
				//'editor_style'    => '', // Registered CSS handle. Enqueued only on the editor page.
				//'script'          => '', // Registered script handle. Global, enqueued on the editor page and frontend.
				//'style'           => '', // Registered CSS handle. Global, enqueued on the editor page and frontend.
				// The callback function used to render the block.
				'render_callback' => array( __CLASS__, 'render' ),
			)
		);
	}

	/**
	 * Callback for the `render_callback` block parameter.
	 *
	 * @since 8.31
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function render( $attributes ) {

		$html = 'DATATABLE';

		return $html;
	}
}
