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

function TaxonomyTypeSelect( {
	                             help,
	                             label,
	                             onChange,
	                             taxonomies,
	                             value,
	                             ...props
                             } ) {

	if ( null === taxonomies ) {
		return (
			<p>
				<Spinner />
				{ __( 'Loading Data', 'school-apps-today' ) }
			</p>
		);
	}

	const options = [];

	taxonomies.forEach( ( taxonomy ) => {

		options.push( { label: taxonomy.name, value: taxonomy.rest_base } )
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
	withSelect( ( select, { taxonomy } ) => {
		return {
			taxonomies: select( 'core' ).getTaxonomies( taxonomy ),
		};
	} ),
] )( TaxonomyTypeSelect );
