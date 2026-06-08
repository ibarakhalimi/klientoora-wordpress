( function () {
	'use strict';

	var panels = document.querySelectorAll( '[data-klientoora-admin-main-panel]' );
	var navLinks = document.querySelectorAll( '.klientoora-admin-main__nav a' );
	var orderStatusStorageKey = 'klientoora-admin-main-order-status-columns-v2';

	function getOrderStatusToggles() {
		return document.querySelectorAll( '[data-klientoora-order-status-toggle]' );
	}

	function getOrderStatusColumns() {
		return document.querySelectorAll( '[data-klientoora-order-status-column]' );
	}

	function getTabFromHash( hash ) {
		return hash ? hash.replace( '#', '' ) : 'dashboard';
	}

	function activateTab( tab ) {
		var selectedPanel = document.querySelector( '[data-klientoora-admin-main-panel="' + tab + '"]' );

		if ( ! selectedPanel ) {
			tab = 'dashboard';
		}

		navLinks.forEach( function ( item ) {
			item.classList.toggle( 'is-active', item.getAttribute( 'href' ) === '#' + tab );
		} );

		panels.forEach( function ( panel ) {
			panel.hidden = panel.dataset.klientooraAdminMainPanel !== tab;
		} );
	}

	function getSavedOrderStatuses() {
		try {
			return JSON.parse( window.localStorage.getItem( orderStatusStorageKey ) || 'null' );
		} catch ( error ) {
			return null;
		}
	}

	function getSelectedOrderStatuses() {
		var selectedStatuses = [];

		getOrderStatusToggles().forEach( function ( toggle ) {
			if ( toggle.checked ) {
				selectedStatuses.push( toggle.value );
			}
		} );

		return selectedStatuses;
	}

	function applyOrderStatusVisibility() {
		var selectedStatuses = getSelectedOrderStatuses();

		getOrderStatusColumns().forEach( function ( column ) {
			var isVisible = selectedStatuses.indexOf( column.dataset.klientooraOrderStatusColumn ) !== -1;

			column.hidden = ! isVisible;
			column.setAttribute( 'aria-hidden', isVisible ? 'false' : 'true' );
			column.classList.toggle( 'is-hidden', ! isVisible );
		} );
	}

	function setupOrderStatusFilters() {
		var savedStatuses = getSavedOrderStatuses();
		var orderStatusToggles = getOrderStatusToggles();

		if ( Array.isArray( savedStatuses ) ) {
			orderStatusToggles.forEach( function ( toggle ) {
				toggle.checked = savedStatuses.indexOf( toggle.value ) !== -1;
			} );
		}

		orderStatusToggles.forEach( function ( toggle ) {
			toggle.addEventListener( 'change', function () {
				try {
					window.localStorage.setItem( orderStatusStorageKey, JSON.stringify( getSelectedOrderStatuses() ) );
				} catch ( error ) {
					// The checkbox state should still update the board when storage is unavailable.
				}

				applyOrderStatusVisibility();
			} );
		} );

		applyOrderStatusVisibility();
	}

	function openOrderDialog( dialogId ) {
		var dialog = document.getElementById( dialogId );

		if ( ! dialog ) {
			return;
		}

		if ( typeof dialog.showModal === 'function' ) {
			dialog.showModal();
			return;
		}

		dialog.setAttribute( 'open', 'open' );
	}

	function closeOrderDialog( dialog ) {
		if ( ! dialog ) {
			return;
		}

		if ( typeof dialog.close === 'function' ) {
			dialog.close();
			return;
		}

		dialog.removeAttribute( 'open' );
	}

	function dismissToast( toast ) {
		if ( ! toast ) {
			return;
		}

		toast.classList.add( 'is-dismissing' );

		window.setTimeout( function () {
			toast.remove();
		}, 220 );
	}

	function setupToasts() {
		document.querySelectorAll( '[data-klientoora-admin-toast]' ).forEach( function ( toast ) {
			window.setTimeout( function () {
				dismissToast( toast );
			}, 5000 );
		} );
	}

	function activateClubActivityTab( tab ) {
		var selectedPanel = document.querySelector( '[data-klientoora-club-activity-panel="' + tab + '"]' );

		if ( ! selectedPanel ) {
			return;
		}

		document.querySelectorAll( '[data-klientoora-club-activity-tab]' ).forEach( function ( button ) {
			var isActive = button.dataset.klientooraClubActivityTab === tab;

			button.classList.toggle( 'is-active', isActive );
			button.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
		} );

		document.querySelectorAll( '[data-klientoora-club-activity-panel]' ).forEach( function ( panel ) {
			panel.hidden = panel.dataset.klientooraClubActivityPanel !== tab;
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '.klientoora-admin-main__nav a' );

		if ( ! link ) {
			return;
		}

		activateTab( getTabFromHash( link.hash ) );
	} );

	document.addEventListener( 'click', function ( event ) {
		var closeButton = event.target.closest( '[data-klientoora-order-dialog-close]' );
		var couponCloseButton = event.target.closest( '[data-klientoora-coupon-dialog-close]' );
		var toastCloseButton = event.target.closest( '[data-klientoora-admin-toast-close]' );
		var clubActivityTab = event.target.closest( '[data-klientoora-club-activity-tab]' );
		var couponCard = event.target.closest( '[data-klientoora-coupon-dialog-trigger]' );
		var orderCard = event.target.closest( '[data-klientoora-order-dialog-trigger]' );

		if ( clubActivityTab ) {
			activateClubActivityTab( clubActivityTab.dataset.klientooraClubActivityTab );
			return;
		}

		if ( toastCloseButton ) {
			dismissToast( toastCloseButton.closest( '[data-klientoora-admin-toast]' ) );
			return;
		}

		if ( closeButton ) {
			closeOrderDialog( closeButton.closest( 'dialog' ) );
			return;
		}

		if ( couponCloseButton ) {
			closeOrderDialog( couponCloseButton.closest( 'dialog' ) );
			return;
		}

		if ( event.target.matches( '.klientoora-admin-main-order-dialog' ) ) {
			closeOrderDialog( event.target );
			return;
		}

		if ( event.target.matches( '.klientoora-admin-main-coupon-dialog' ) ) {
			closeOrderDialog( event.target );
			return;
		}

		if ( couponCard ) {
			openOrderDialog( couponCard.dataset.klientooraCouponDialogTrigger );
			return;
		}

		if ( orderCard ) {
			openOrderDialog( orderCard.dataset.klientooraOrderDialogTrigger );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		var couponCard = event.target.closest( '[data-klientoora-coupon-dialog-trigger]' );
		var orderCard = event.target.closest( '[data-klientoora-order-dialog-trigger]' );

		if ( couponCard && ( event.key === 'Enter' || event.key === ' ' ) ) {
			event.preventDefault();
			openOrderDialog( couponCard.dataset.klientooraCouponDialogTrigger );
			return;
		}

		if ( ! orderCard || ( event.key !== 'Enter' && event.key !== ' ' ) ) {
			return;
		}

		event.preventDefault();
		openOrderDialog( orderCard.dataset.klientooraOrderDialogTrigger );
	} );

	window.addEventListener( 'hashchange', function () {
		activateTab( getTabFromHash( window.location.hash ) );
	} );

	setupOrderStatusFilters();
	setupToasts();
	activateTab( getTabFromHash( window.location.hash ) );
}() );
