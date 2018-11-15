/* ================================================
 * jquery-ui-autocomplete-with-clear-button.js v0.0.1
 *
 * Extends jQuery UI Autocomplete widget with a button that clears away the current value of the autocomplete <input> element.
 * The following options are available:
 * - `clearButton` -  type: Boolean, default: true - adds a button that will clear the autocomplete input
 * - `clearButtonHtml`- type: String, default: '&times;' - the content of the button
 * - `clearButtonPosition` - type: Object|Boolean, default: {my: "right center", at: "right center"} - an object with the parameters needed to position the button using jQuery UI Position (https://api.jqueryui.com/position/). Set it to `false` if you want to position the button via CSS.
 * ============================================= */
(function($) {

	$.widget( "ui.autocomplete", $.ui.autocomplete, {

		// extend default options
		options : {
			clearButton: true,
			clearButtonHtml: '&times;',
			clearButtonPosition: {
				my: "right center",
				at: "right center"
			}
		},

		_create: function() {

			var self = this;

			// Invoke the parent widget's method.
			self._super();

			if ( self.options.clearButton ) {
				self._createClearButton();
			}

		},

		_createClearButton: function() {

			var self = this;

			self.clearElement = $("<span>")
				.attr( 'tabindex', '-1' )
				.addClass( 'ui-autocomplete-clear' )
				.html( self.options.clearButtonHtml )
				.css( 'height', $( self.element ).height() )
				.css( 'width', $( self.element ).height() )
				.insertAfter( self.element );

			if ( self.options.clearButtonPosition !== false && typeof self.options.clearButtonPosition === 'object' ) {
				if ( typeof self.options.clearButtonPosition.of === 'undefined' ) {
					self.options.clearButtonPosition.of = self.element;
				}
				self.clearElement.position( self.options.clearButtonPosition );
			}

			self._on( self.clearElement, {
				click: function() {
					self.element.val('').focus();
					self._hideClearButton();
				}
			});

			self.element.addClass( 'ui-autocomplete-input-has-clear' );

			self._on( self.element, {
				input: function() {
					if ( self.element.val()!=="" ) {
						self._showClearButton();
					} else {
						self._hideClearButton();
					}
				}
			});

			self._on( self.menu.element, {
				menuselect: function() {
					if ( self.element.val()!=="" ) {
						self._showClearButton();
					} else {
						self._hideClearButton();
					}
				}
			});

			// show clearElement if input has some content on initialization
			if( self.element.val()!=="" ) {
				self._showClearButton();
			} else {
				self._hideClearButton();
			}

		},

		_showClearButton: function() {
			this.clearElement.css({'display': 'inline-block'});
		},

		_hideClearButton: function() {
			this.clearElement.css({'display': 'none'});
		}

	});

})(jQuery);

(function( $ ) {

	// Init the delete posts by category button.
	$( 'button.rbd-posts-in-category' ).on( 'click', function( e ) {

		e.preventDefault();

		var $this = $( this );

		if ( confirm( $this.data( 'confirm' ) ) ) {

			var text = $this.text();
			var url  = $this.data( 'url' );
			var key  = $this.data( 'key' );
			var cat  = $this.data( 'category' );

			$.ajax({
				method:     'DELETE',
				url:        url + 'wp-json/rbd-api/v1/delete-posts-in/' + cat,
				dataType:   'json',
				beforeSend: function( xhr ) {

					xhr.setRequestHeader( 'Authorization', 'Basic ' + key );

					var matcher = new RegExp( '^' + url, 'i' );

					if ( matcher.test( wpApiSettings.root ) ) {

						xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
					}

					$this.prop( 'disabled', true ).text( 'Processing, please wait.' );
				},
				success: function( response ) {
					console.log( response );
					$this.prop( 'disabled', false ).text( text );
				},
				error: function( response ) {
					console.log( response );
					$this.prop( 'disabled', false ).text( text );
				},
				failure: function( response ) {
					console.log( response );
					$this.prop( 'disabled', false ).text( text );
				}
			});

		}

	});

})( jQuery );

(function( $ ) {

	// Init the delete post buttons.
	$( 'button.rbd-post' ).on( 'click', function( e ) {

		e.preventDefault();

		var $this = $( this );

		if ( confirm( $this.data( 'confirm' ) ) ) {

			var text = $this.text();
			var url  = $this.data( 'url' );
			var key  = $this.data( 'key' );
			var post = $this.attr( 'data-post' );

			$.ajax({
				method:     'DELETE',
				url:        url + 'wp-json/wp/v2/posts/' + post,
				dataType:   'json',
				beforeSend: function( xhr ) {

					xhr.setRequestHeader( 'Authorization', 'Basic ' + key );

					var matcher = new RegExp( '^' + url, 'i' );

					if ( matcher.test( wpApiSettings.root ) ) {

						xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
					}

					$this.prop( 'disabled', true ).text( 'Processing, please wait.' );
				},
				success: function( response ) {
					console.log( response );
					reset( $this );
				},
				error: function( response ) {
					console.log( response );
					reset( $this );
					alert( response.responseJSON.message );
				},
				failure: function( response ) {
					console.log( response );
					reset( $this );
				}
			});

			// Helper function to reset the delete post button.
			function reset( element ) {

				var input = $this.closest( 'div.rbd-post-fieldset' ).find( 'input.ui-autocomplete-input' );
				input.val('');

				var clear = $this.closest( 'div.rbd-post-fieldset' ).find( '.ui-autocomplete-clear' );
				clear.trigger( 'click' );

				element.prop( 'disabled', true ).attr( 'data-post', null ).text( text );
			}

		}

	});

})( jQuery );

(function( $ ) {

	// Init the autocomplete fields.
	$( 'input.ui-autocomplete-input' ).each( function() {

		var $this = $( this );
		var searchRequest;

		$this.autocomplete({
			delay:     500,
			minLength: 3,
			autoFocus: true,
			clearButton: true,
			clearButtonHtml: '',
			clearButtonPosition: {
				my: "right center",
				at: "right-6 center"
			},
			source:    function( request, response ) {

				try {
					searchRequest.abort();
				} catch ( e ) {
				}

				var url   = $this.data( 'url' );

				// Perform the post search.
				searchRequest = $.ajax({
					method:   'GET',
					url:      url + 'wp-json/rbd-api/v1/search/',
					data:     {
						category: $this.data( 'category' ),
						term:     request.term
					},
					dataType: 'json',
					success:  function( results ) {

						var suggested = [];

						if ( results !== null ) {

							for ( var key in results ) {

								var post     = results[ key ];
								var postDate = new Date( post.post_date_gmt + ' UTC' );

								var postDateFormatted = postDate.toLocaleDateString( 'en-US' );
								// var postDateFormatted = postDate.toLocaleString( 'en-US' );

								// console.log( post );

								// Build the search results object.
								var item = {
									id:    post.ID,
									value: post.post_title + ' (' + postDateFormatted + ')'
								};

								suggested.push( item )
							}

						}

						// NOTE: You must always call the response callback even if you encounter an error.
						// This ensures that the widget always has the correct state.
						response( suggested );
					}
				});
			},
			search:    function() {

				// Remove the post ID data attribute and disable the delete button when performing a search.
				var button = $this.closest( 'div.rbd-post-fieldset' ).find( 'button.rbd-post' );
				button.prop( 'disabled', true ).attr( 'data-post', null );

				// Do not show the clear text button while performing a search.
				var clear = $this.closest( 'div.rbd-post-fieldset' ).find( '.ui-autocomplete-clear' );
				clear.css( { 'display': 'none' } );

				// Display a loading spinner while fetching search results.
				$this.addClass( 'ui-autocomplete-is-loading' );
			},
			response:  function( event, ui ) {

				// Remove the loading spinner.
				$this.removeClass( 'ui-autocomplete-is-loading' );

				// Display the clear text button. NOTE Perhaps this should have a check to make sure the field has text first?
				var clear = $this.closest( 'div.rbd-post-fieldset' ).find( '.ui-autocomplete-clear' );
				clear.css( { 'display': 'inline-block' } );

				// If there are no search results, display a no results found message.
				if ( ! ui.content.length ) {

					var noResult = { id: 0, value: '', label: 'No results found' };
					ui.content.push( noResult );
				}

			},
			select: function( event, ui ) {

				// Add the selected post ID to the delete button as a data attribute.
				if ( 0 < ui.item.id ) {

					var button = $this.closest( 'div.rbd-post-fieldset' ).find( 'button.rbd-post' );
					button.prop( 'disabled', false ).attr( 'data-post', ui.item.id );

				}

			},
			close: function( event, ui ) {

				var ev = event.originalEvent;

				// Clear the input if the escape key was hit.
				if ( ev.type === 'keydown' && ev.keyCode === $.ui.keyCode.ESCAPE ) {
					$this.val( '' );
				}

				// If the input is empty, disable the clear text button and remove the post ID data attribute.
				// Remove the clear text button.
				if ( '' === $this.val() ) {

					var button = $this.closest( 'div.rbd-post-fieldset' ).find( 'button.rbd-post' );
					button.prop( 'disabled', true ).attr( 'data-post', null );

					var clear = $this.closest( 'div.rbd-post-fieldset' ).find( '.ui-autocomplete-clear' );
					clear.trigger( 'click' );
				}
			}
		});
	});

	// Remove the post ID data attribute and disable the delete button when the clear text button is clicked.
	$( '.ui-autocomplete-clear' ).on( 'click', function() {

		var button = $( this ).closest( 'div.rbd-post-fieldset' ).find( 'button.rbd-post' );
		button.prop( 'disabled', true ).attr( 'data-post', null );
	});

})( jQuery );
