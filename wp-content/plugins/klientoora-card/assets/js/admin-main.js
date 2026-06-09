( function () {
	'use strict';

	var panels = document.querySelectorAll( '[data-klientoora-admin-main-panel]' );
	var navLinks = document.querySelectorAll( '[data-klientoora-main-nav]' );
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

	function getUrlParams() {
		return new URLSearchParams( window.location.search );
	}

	function hasMainTab( tab ) {
		return !! document.querySelector( '[data-klientoora-admin-main-panel="' + tab + '"]' );
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

	function getInitialMainTab() {
		var params = getUrlParams();
		var requestedTab = params.get( 'klientoora_admin_main_tab' );

		if ( requestedTab && hasMainTab( requestedTab ) ) {
			return requestedTab;
		}

		if (
			params.has( 'klientoora_card_coupon_notice' )
		) {
			return 'club-coupons';
		}

		if ( params.has( 'klientoora_card_challenge_notice' ) ) {
			return 'challenges';
		}

		if ( params.has( 'klientoora_card_product_redemption_notice' ) ) {
			return 'point-redemptions';
		}

		if ( params.has( 'klientoora_card_products_notice' ) ) {
			return 'products';
		}

		if ( params.has( 'klientoora_card_orders_notice' ) ) {
			return 'orders';
		}

		return getTabFromHash( window.location.hash );
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

	function applyProductFilters() {
		var categoryFilter = document.querySelector( '[data-klientoora-product-category-filter]' );
		var visibilityFilter = document.querySelector( '[data-klientoora-product-visibility-filter]' );
		var selectedCategory = categoryFilter ? categoryFilter.value : '';
		var selectedVisibility = visibilityFilter ? visibilityFilter.value : '';

		document.querySelectorAll( '[data-klientoora-product-row]' ).forEach( function ( row ) {
			var rowCategories = row.dataset.categoryIds ? row.dataset.categoryIds.split( ',' ) : [];
			var rowVisibility = row.dataset.productVisibility || '';
			var categoryMatch = ! selectedCategory || rowCategories.indexOf( selectedCategory ) !== -1;
			var visibilityMatch = ! selectedVisibility || rowVisibility === selectedVisibility;

			row.hidden = ! ( categoryMatch && visibilityMatch );
		} );
	}

	function setupProductFilters() {
		document.querySelectorAll( '[data-klientoora-product-category-filter], [data-klientoora-product-visibility-filter]' ).forEach( function ( filter ) {
			filter.addEventListener( 'change', applyProductFilters );
		} );

		applyProductFilters();
	}

	function setProductImageSelection( attachment, imageControl ) {
		var scope = imageControl || document;
		var imageInput = scope.querySelector( '[data-klientoora-product-image-id]' );
		var preview = scope.querySelector( '[data-klientoora-product-image-preview]' );
		var imageUrl = '';
		var image = null;
		var placeholder = null;

		if ( ! imageInput || ! preview ) {
			return;
		}

		preview.textContent = '';

		if ( attachment && attachment.id ) {
			imageInput.value = attachment.id;
			imageUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

			if ( imageUrl ) {
				image = document.createElement( 'img' );
				image.src = imageUrl;
				image.alt = '';
				preview.appendChild( image );
				return;
			}

			placeholder = document.createElement( 'span' );
			placeholder.textContent = attachment.filename || 'נבחרה תמונה';
			preview.appendChild( placeholder );
			return;
		}

		imageInput.value = '';
		placeholder = document.createElement( 'span' );
		placeholder.textContent = 'לא נבחרה תמונה';
		preview.appendChild( placeholder );
	}

	function openProductImagePicker( imageControl ) {
		var frame = null;

		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		frame = window.wp.media( {
			title: 'בחירת תמונת מוצר',
			button: {
				text: 'בחירת תמונה'
			},
			multiple: false,
			library: {
				type: 'image'
			}
		} );

		frame.on( 'select', function () {
			setProductImageSelection( frame.state().get( 'selection' ).first().toJSON(), imageControl );
		} );

		frame.open();
	}

	function activateInitialClubActivityTab() {
		var params = getUrlParams();
		var requestedTab = params.get( 'klientoora_club_activity_tab' );

		if ( requestedTab && hasMainTab( requestedTab ) ) {
			activateTab( requestedTab );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '[data-klientoora-main-nav]' );
		var tab = link ? getTabFromHash( link.hash ) : '';

		if ( ! link ) {
			return;
		}

		if ( ! hasMainTab( tab ) ) {
			return;
		}

		event.preventDefault();
		activateTab( tab );

		if ( window.location.hash !== link.hash ) {
			window.history.pushState( null, '', link.hash );
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		var closeButton = event.target.closest( '[data-klientoora-order-dialog-close]' );
		var couponCloseButton = event.target.closest( '[data-klientoora-coupon-dialog-close]' );
		var toastCloseButton = event.target.closest( '[data-klientoora-admin-toast-close]' );
		var couponCard = event.target.closest( '[data-klientoora-coupon-dialog-trigger]' );
		var productDialogTrigger = event.target.closest( '[data-klientoora-product-dialog-trigger]' );
		var productDialogCloseButton = event.target.closest( '[data-klientoora-product-dialog-close]' );
		var categoryDialogTrigger = event.target.closest( '[data-klientoora-category-dialog-trigger]' );
		var categoryDialogCloseButton = event.target.closest( '[data-klientoora-category-dialog-close]' );
		var memberDialogTrigger = event.target.closest( '[data-klientoora-member-dialog-trigger]' );
		var memberDialogCloseButton = event.target.closest( '[data-klientoora-member-dialog-close]' );
		var productImageSelect = event.target.closest( '[data-klientoora-product-image-select]' );
		var productImageClear = event.target.closest( '[data-klientoora-product-image-clear]' );
		var orderCard = event.target.closest( '[data-klientoora-order-dialog-trigger]' );

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

		if ( productDialogCloseButton ) {
			closeOrderDialog( productDialogCloseButton.closest( 'dialog' ) );
			return;
		}

		if ( categoryDialogCloseButton ) {
			closeOrderDialog( categoryDialogCloseButton.closest( 'dialog' ) );
			return;
		}

		if ( memberDialogCloseButton ) {
			closeOrderDialog( memberDialogCloseButton.closest( 'dialog' ) );
			return;
		}

		if ( event.target.matches( '.klientoora-admin-main-product-dialog' ) ) {
			closeOrderDialog( event.target );
			return;
		}

		if ( productImageSelect ) {
			openProductImagePicker( productImageSelect.closest( '.klientoora-admin-main-product-dialog__image-control' ) );
			return;
		}

		if ( productImageClear ) {
			setProductImageSelection( null, productImageClear.closest( '.klientoora-admin-main-product-dialog__image-control' ) );
			return;
		}

		if ( event.target.matches( '.klientoora-admin-main-member-dialog' ) ) {
			closeOrderDialog( event.target );
			return;
		}

		if ( couponCard ) {
			openOrderDialog( couponCard.dataset.klientooraCouponDialogTrigger );
			return;
		}

		if ( productDialogTrigger ) {
			openOrderDialog( productDialogTrigger.dataset.klientooraProductDialogTrigger );
			return;
		}

		if ( categoryDialogTrigger ) {
			openOrderDialog( categoryDialogTrigger.dataset.klientooraCategoryDialogTrigger );
			return;
		}

		if ( memberDialogTrigger ) {
			openOrderDialog( memberDialogTrigger.dataset.klientooraMemberDialogTrigger );
			return;
		}

		if ( orderCard ) {
			openOrderDialog( orderCard.dataset.klientooraOrderDialogTrigger );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		var couponCard = event.target.closest( '[data-klientoora-coupon-dialog-trigger]' );
		var productDialogTrigger = event.target.closest( '[data-klientoora-product-dialog-trigger]' );
		var categoryDialogTrigger = event.target.closest( '[data-klientoora-category-dialog-trigger]' );
		var memberDialogTrigger = event.target.closest( '[data-klientoora-member-dialog-trigger]' );
		var orderCard = event.target.closest( '[data-klientoora-order-dialog-trigger]' );

		if ( couponCard && ( event.key === 'Enter' || event.key === ' ' ) ) {
			event.preventDefault();
			openOrderDialog( couponCard.dataset.klientooraCouponDialogTrigger );
			return;
		}

		if ( productDialogTrigger && ( event.key === 'Enter' || event.key === ' ' ) ) {
			event.preventDefault();
			openOrderDialog( productDialogTrigger.dataset.klientooraProductDialogTrigger );
			return;
		}

		if ( categoryDialogTrigger && ( event.key === 'Enter' || event.key === ' ' ) ) {
			event.preventDefault();
			openOrderDialog( categoryDialogTrigger.dataset.klientooraCategoryDialogTrigger );
			return;
		}

		if ( memberDialogTrigger && ( event.key === 'Enter' || event.key === ' ' ) ) {
			event.preventDefault();
			openOrderDialog( memberDialogTrigger.dataset.klientooraMemberDialogTrigger );
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
	setupProductFilters();
	setupToasts();
	activateTab( getInitialMainTab() );
	activateInitialClubActivityTab();
}() );
