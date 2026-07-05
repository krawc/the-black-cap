(function () {
	'use strict';

	var mediaFrame  = null;
	var currentIds  = [];

	document.addEventListener( 'DOMContentLoaded', function () {
		var btn   = document.getElementById( 'tbc-venue-media-btn' );
		var input = document.getElementById( 'tbc-venue-image-ids' );
		var grid  = document.getElementById( 'tbc-venue-img-grid' );
		var count = document.getElementById( 'tbc-venue-img-count' );

		if ( ! btn ) return;

		currentIds = input.value
			? input.value.split( ',' ).map( Number ).filter( Boolean )
			: [];

		btn.addEventListener( 'click', function () {
			if ( ! mediaFrame ) {
				mediaFrame = wp.media( {
					title:    'Select Venue Images',
					button:   { text: 'Set images' },
					multiple: true,
				} );

				mediaFrame.on( 'open', function () {
					var selection = mediaFrame.state().get( 'selection' );
					currentIds.forEach( function ( id ) {
						var att = wp.media.attachment( id );
						att.fetch();
						selection.add( att );
					} );
				} );

				mediaFrame.on( 'select', function () {
					var attachments = mediaFrame.state().get( 'selection' ).toJSON();
					currentIds = attachments.map( function ( a ) { return a.id; } );

					input.value = currentIds.join( ',' );

					grid.innerHTML = '';
					attachments.forEach( function ( a ) {
						var thumb = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
						var img   = document.createElement( 'img' );
						img.src   = thumb;
						img.alt   = '';
						grid.appendChild( img );
					} );

					btn.textContent   = 'Edit Images';
					count.textContent = currentIds.length + ' image(s)';
				} );
			}

			mediaFrame.open();
		} );
	} );
} )();
