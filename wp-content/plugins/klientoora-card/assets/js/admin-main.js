( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '.klientoora-admin-main__nav a' );

		if ( ! link ) {
			return;
		}

		document.querySelectorAll( '.klientoora-admin-main__nav a' ).forEach( function ( item ) {
			item.classList.remove( 'is-active' );
		} );

		link.classList.add( 'is-active' );
	} );
}() );
