(function( $ ) {

	/**
	 * @var {{root: string, nonce: string}} pageloaderAPISettings
	 */

	/**
	 * Get Pageloader Settings from remote site via the WP REST API.
	 *
	 * @param {string} apiURL The WP REST API Pageloader settings endpoint.
	 *
	 * @returns {JQuery.jqXHR}
	 */
	function getRemoteValues( apiURL ) {

		return $.ajax({
			method:   'GET',
			url:      apiURL + 'wp-json/pageloader-api/v1/settings',
			dataType: 'json',
		});
	}

	/**
	 * Set the Pageloader settings on a remote site.
	 *
	 * @param {string}   apiURL
	 * @param {string}   appKey
	 * @param {FormData} data
	 *
	 * @returns {JQuery.jqXHR}
	 */
	function setRemoteValues( apiURL, appKey, data ) {

		return $.ajax({
			method:      'POST',
			url:         apiURL + 'wp-json/pageloader-api/v1/settings',
			dataType:    'json',
			// data:        {
			// 	bonfire_pageloader_custom_loading_text: options.text,
			// 	pageloader_custom_loading_image: options.image,
			// },
			data:        data,
			processData: false, //Very important
			contentType: false, //Very important
			beforeSend:  function( xhr ) {

				xhr.setRequestHeader( 'Authorization', 'Basic ' + appKey );

				const matcher = new RegExp( '^' + apiURL, 'i' );

				if ( matcher.test( pageloaderAPISettings.root ) ) {

					xhr.setRequestHeader( 'X-WP-Nonce', pageloaderAPISettings.nonce );
				}

			}
		})
			.done( function( response ) {
				console.log( response );
			} )
			.fail( function( response ) {
				console.log( response );

				if ( typeof response.responseJSON !== 'undefined' /*|| null !== response.responseJSON.message*/ ) {
					alert( response.responseJSON.message );
				}
			});
	}

	/**
	 * @param {{file: File,pageloader_custom_loading_image:string,bonfire_pageloader_custom_loading_text: *}} options
	 *
	 * @return {FormData}
	 */
	function getFormData( options ) {

		// let loadingImage = fieldset.find( '.pageloader-choose-image input' )[0].files[0];
		// let loadingText  = fieldset.find( '.pageloader-text-option input' ).val();

		let data = new FormData();

		data.append( 'file', options.file );
		data.append( 'pageloader_custom_loading_image', options.pageloader_custom_loading_image );
		data.append( 'bonfire_pageloader_custom_loading_text', options.bonfire_pageloader_custom_loading_text );

		return data;
	}

	/**
	 * Get form fieldset values.
	 *
	 * @param {JQuery} fieldset
	 *
	 * @return {{file: File,pageloader_custom_loading_image:string,bonfire_pageloader_custom_loading_text: *}}
	 */
	function getFormValues( fieldset ) {

		let loadingFile  = fieldset.find( '.pageloader-choose-image input' )[0].files[0];
		let loadingImage = fieldset.find( 'img.pageloader-image' ).attr( 'src' );
		let loadingText  = fieldset.find( '.pageloader-text-option input' ).val();

		return {
			file: loadingFile,
			pageloader_custom_loading_image: loadingImage,
			bonfire_pageloader_custom_loading_text:  loadingText,
		};
	}

	/**
	 * Update form fieldset values.
	 *
	 * @param {JQuery} fieldset
	 * @param {{file: File,pageloader_custom_loading_image:string,bonfire_pageloader_custom_loading_text: *}} options
	 */
	function updateForm( fieldset, options ) {

		const image = fieldset.find( 'img.pageloader-image' );
		const text  = fieldset.find( '.pageloader-text-option input' );

		if ( 0 < options.pageloader_custom_loading_image.length ) {

			image.attr( 'src', options.pageloader_custom_loading_image );

			fieldset.find( '.pageloader-choose-image' ).slideUp( function() {

				fieldset.find( '.pageloader-manage-image' ).slideDown();
			});

		} else {

			fieldset.find( '.pageloader-manage-image' ).slideUp( function() {

				fieldset.find( '.pageloader-choose-image' ).slideDown();
			});

		}

		text.val( options.bonfire_pageloader_custom_loading_text );
	}

	// Init the Pageloader fieldset with remote values.
	$( '.pageloader-options' ).each( function( index, element ) {

		const $this = $( element );

		const button  = $this.find( 'button.pageloader-save' );
		const loading = $this.find( '.pageloader-options-loading-overlay' );
		const url     = button.data( 'url' );

		$( document ).on( {
			ajaxStart: function() {
				loading.css( 'display', 'block' );
			},
			ajaxStop:  function() {
				loading.css( 'display', 'none' );
			}
		});

		getRemoteValues( url ).done( function( response ) {
			console.log( response );

			updateForm( $this, response );
		});
	});

	// Init the "Save" button.
	$( 'button.pageloader-save' ).on( 'click', function( e ) {

		e.preventDefault();

		const $this    = $( this );
		const fieldset = $this.closest( '.pageloader-options' );

		const url = $this.data( 'url' );
		const key = $this.data( 'key' );

		const data = getFormData( getFormValues( fieldset ) );

		// console.log( data );

		setRemoteValues( url, key, data ).done( function( response ) {

			updateForm( fieldset, response )
		});
	});

	// Init the "Remove" image button.
	$( 'button.pageloader-image-remove, button.pageloader-image-change' ).on( 'click', function( e ) {

		e.preventDefault();

		const $this    = $( this );
		const fieldset = $this.closest( '.pageloader-options' );
		const image    = fieldset.find( 'img.pageloader-image' );

		fieldset.find( '.pageloader-manage-image' ).slideUp( function() {

			image.attr( 'src', '' );

			const options = getFormValues( fieldset );

			updateForm( fieldset, options );
		});

	});

})( jQuery );
