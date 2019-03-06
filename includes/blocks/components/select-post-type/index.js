/**
 * External dependencies
 */
// const { get } = lodash;

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { compose } = wp.compose;
const { withSelect } = wp.data;
const {
	      Spinner,
	      SelectControl,
      } = wp.components;

function PostTypeSelect( {
	                         help,
	                         label,
	                         onChange,
	                         postTypes,
	                         value,
	                         ...props
                         } ) {

	if ( null === postTypes ) {
		return (
			<p>
				<Spinner />
				{ __( 'Loading Data', 'school-apps-today' ) }
			</p>
		);
	}

	const options = [];

	postTypes.forEach( ( post ) => {

		if ( 0 < post.taxonomies.length ) {

			options.push( { label: post.name, value: post.slug } )
		}
	} );

	return (
		<SelectControl
			label={ label }
			help={ help }
			value={ value }
			options={ options }
			onChange={ onChange }
		/>
	);
}

export default compose( [
	withSelect( ( select ) => {
		return {
			postTypes: select( 'core' ).getPostTypes(),
		};
	} ),
] )( PostTypeSelect );
