/**
 * External dependencies
 */
import ReactTable from "react-table";
import selectTable from "react-table/lib/hoc/selectTable";
import 'react-table/react-table.css'

const SelectTable = selectTable( ReactTable );

/**
 * WordPress dependencies
 */
const { __, _x, _n, sprintf } = wp.i18n;
const { apiFetch } = wp;
const { compose } = wp.compose;
const { withSelect } = wp.data;
const { Component } = wp.element;
const { addQueryArgs } = wp.url;

/**
 * Module Constants
 */
const DEFAULT_QUERY = {
	per_page: - 1,
	orderby:  'name',
	order:    'asc',
	// _fields:  'id,name,parent',
};


class TermDatatable extends Component {

	constructor() {

		super( ...arguments );

		const { perPage, taxonomy } = this.props;

		this.fetchTerms = this.fetchTerms.bind( this );
		this.renderEditable = this.renderEditable.bind( this );

		this.state = {
			data:     [],
			loading:  true,
			page:     0,
			pages:    null,
			perPage:  perPage,
			taxonomy: taxonomy,
		};

		console.log( 'constructorDidRun', true );
	}

	componentDidMount() {
		console.log( 'componentDidMount', true );
	}

	componentWillUnmount() {
		console.log( 'componentWillUnmount', true );
	}

	componentDidUpdate() {

		if ( this.props.taxonomy !== this.state.taxonomy ) {

			console.log( 'componentDidUpdate(state)', this.state.taxonomy );
			console.log( 'componentDidUpdate(props)', this.props.taxonomy );
			// this.table.fireFetchData();

			const state = {
				data:     [],
				page:     0,
				pages:    null,
				taxonomy: this.props.taxonomy,
			};

			this.setState( state );

			console.log( state );
			this.fetchTerms( state );
		}

		console.log( 'componentDidUpdate', true );
	}

	fetchTerms( state, instance ) {

		this.setState( {
			loading: true,
			// page:    state.page,
		} );

		const { taxonomy } = this.props;

		console.log( 'fetchData', taxonomy );

		if ( ! taxonomy ) {

			return;
		}

		const queryArgs = {
			...DEFAULT_QUERY,
			per_page: state.pageSize,
		};

		if ( 1 <= state.page ) {
			queryArgs.page = state.page + 1; // ReactTable page 1 is 0, add 1 to get the correct page from the REST API.
		}

		this.fetchRequest = apiFetch( {
			parse: false,
			path:  addQueryArgs( `/wp/v2/${ taxonomy }/`, queryArgs ),
		} );

		this.fetchRequest
			.then(
				( response ) => {

					const totalPages = response.headers && response.headers.get( 'X-WP-TotalPages' );

					this.setState( {
						pages: totalPages,
					} );

					return response.json();
				},
				( xhr ) => { // reject
					if ( xhr.statusText === 'abort' ) {
						return;
					}
					this.fetchRequest = null;
					this.setState( {
						loading: false,
					} );
				}
			)
			.then(
				( data ) => {

					// const availableTermsTree = this.sortBySelected( buildTermsTree( data ) );

					this.fetchRequest = null;
					this.setState( {
						loading: false,
						data:    data,
					} );
				}
			);

	}

	renderEditable( cellInfo ) {
		return (
			<div
				style={ { backgroundColor: "#fafafa" } }
				contentEditable
				suppressContentEditableWarning
				onBlur={ e => {
					const data = [ ...this.state.data ];
					data[ cellInfo.index ][ cellInfo.column.id ] = e.target.innerHTML;
					this.setState( { data } );
				} }
				dangerouslySetInnerHTML={ {
					__html: this.state.data[ cellInfo.index ][ cellInfo.column.id ]
				} }
			/>
		);
	}

	changePage( pageIndex ) {
		console.log( `changePage(pageIndex: ${ pageIndex })` );
		this.setState( {
			page: pageIndex
		} );
	}

	render() {

		// const { perPage, taxonomy, } = this.props;

		const { data, loading, page, pages, perPage, taxonomy } = this.state;

		return (
			<SelectTable
				className='-striped -highlight'
				columns={ [
					{
						Header:   'Name',
						accessor: 'name',
						Cell:     this.renderEditable,
					},
					{
						Header: 'Description',
						id:     'description',
					},
					{
						Header:   'Slug',
						accessor: 'slug',
					},
					{
						Header:   'Count',
						accessor: 'count',
					},
					{
						Header:   'ID',
						accessor: 'id',
					},
				] }
				data={ data }
				defaultPageSize={ perPage }
				onFetchData={ state => this.fetchTerms( state ) }
				keyField='id'
				loading={ loading }
				manual
				onPageChange={ pageIndex => this.changePage( pageIndex ) }
				page={ page }
				pages={ pages }
				selectType={ 'checkbox' }
			/>
		);
	}
}

export default TermDatatable;

// export default compose( [
// 	withSelect( ( select, { taxonomy } ) => {
//
// 		return {
// 			taxonomy,
// 		};
// 	} ),
// ] )( TermDatatable );
