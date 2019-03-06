/**
 * External dependencies
 */
import ReactTable from "react-table";
import 'react-table/react-table.css'

import namor from "namor";

/**
 * WordPress dependencies
 */
const { __, _n, _nx, _x } = wp.i18n;
const { registerBlockType } = wp.blocks;
const {
	      InspectorControls,
	      InspectorAdvancedControls,
      } = wp.editor;
const {
	      ExternalLink,
	      PanelBody,
	      // RangeControl,
	      RadioControl,
	      SelectControl,
	      ServerSideRender,
	      TextControl,
	      ToggleControl,
      } = wp.components;

const {
	      // Component,
	      Fragment,
      } = wp.element;

/**
 * Internal dependencies
 */
import TermDatatable from '../components/term-datatable/';
import PostTypeSelect from '../components/select-post-type/'
import TaxonomyTypeSelect from '../components/select-taxomony/'

// Import CSS
// import './styles/editor.scss';
// import './styles/public.scss';

/**
 * Register Block
 */
registerBlockType(
	'school-apps-today/category-datatable',
	{
		title:       __( 'Category Datatable', 'sat/admin-dashboard' ),
		description: __( 'Display a data table which allows the user to edit category data.', 'sat/admin-dashboard' ),
		category:    'school-apps-today',
		// icon:        giveLogo,
		keywords:    [
			'sat',
			__( 'category', 'school-apps-today' ),
		],
		supports:    {
			// Remove the support for the generated className.
			className:       false,
			// Remove the support for the custom className.
			customClassName: false,
			// Remove the support for editing the block using the block HTML editor.
			html:            false,
		},
		attributes:  {
			postType: {
				type:    'string',
				default: 'post',
			},
			taxonomy: {
				type:    'string',
				default: 'categories',
			},
		},
		edit:        function( { attributes, setAttributes } ) {

			const {
				      postType,
				      taxonomy,
			      } = attributes;

			return (
				<Fragment>
					<InspectorControls>
						<PanelBody title={ __( 'Settings', 'school-apps-today' ) }>

							<PostTypeSelect
								label={ __( 'Post Type', 'school-apps-today' ) }
								help={ __( 'Choose the post type to choose the taxonomy.', 'school-apps-today' ) }
								value={ postType }
								onChange={ ( value ) => setAttributes( { postType: value } ) }
							/>

							<TaxonomyTypeSelect
								label={ __( 'Taxonomy', 'school-apps-today' ) }
								help={ __( 'Choose the taxonomy to use for the datatable.', 'school-apps-today' ) }
								value={ taxonomy }
								onChange={ ( value ) => setAttributes( { taxonomy: value } ) }
							/>

						</PanelBody>
					</InspectorControls>
					<InspectorAdvancedControls>
					</InspectorAdvancedControls>

					<TermDatatable
						perPage={ 10 }
						taxonomy={ taxonomy }
					/>

				</Fragment>
			);

		},
		save:        function() {
			// Server side rendering via shortcode.
			return null;
		},
	}
);
