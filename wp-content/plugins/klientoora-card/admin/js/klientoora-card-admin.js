( function ( $ ) {
	'use strict';

	$( function () {
		var $activeOrderModal = null;

		function closeOrderModal() {
			if ( ! $activeOrderModal ) {
				return;
			}

			$activeOrderModal.attr( 'hidden', true );
			$( 'body' ).removeClass( 'klientoora-card-order-modal-open' );
			$activeOrderModal = null;
		}

		function openOrderModal( modalId ) {
			var $modal = $( '#' + modalId );

			if ( ! $modal.length ) {
				return;
			}

			closeOrderModal();
			$activeOrderModal = $modal;
			$modal.removeAttr( 'hidden' );
			$( 'body' ).addClass( 'klientoora-card-order-modal-open' );
			$modal.find( '[data-klientoora-card-close-order-modal]' ).first().trigger( 'focus' );
		}

		$( document ).on( 'click', '[data-klientoora-card-open-order-modal]', function () {
			openOrderModal( $( this ).attr( 'aria-controls' ) );
		} );

		$( document ).on( 'keydown', '[data-klientoora-card-open-order-modal]', function ( event ) {
			if ( 'Enter' === event.key || ' ' === event.key ) {
				event.preventDefault();
				openOrderModal( $( this ).attr( 'aria-controls' ) );
			}
		} );

		$( document ).on( 'click', '[data-klientoora-card-close-order-modal]', function () {
			closeOrderModal();
		} );

		$( document ).on( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				closeOrderModal();
			}
		} );
	} );
}( jQuery ) );
