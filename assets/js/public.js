(function( $ ) {

	$( 'button.rbd' ).on( 'click', function( e ) {

		e.preventDefault();

		if ( confirm( 'Are you sure?' ) ) {

			var $this = $( this );

			var text = $this.text();
			var url  = $this.data( 'url' );
			var key  = $this.data( 'key' );
			var cat  = $this.data( 'category' );

			$.ajax( {
				method:     'DELETE',
				url:        url + 'wp-json/rbd-api/v1/delete-posts-in/' + cat,
				dataType:   'json',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'Authorization', 'Basic ' + key );
					$this.prop( 'disabled', true ).text( 'Processing, please wait.' );
				},
				success: function( response ) {
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
