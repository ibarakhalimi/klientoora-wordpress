( function () {
	'use strict';

	var activeTarget = null;
	var activeTrigger = null;
	var closeTimer = null;

	function getOverlay() {
		return document.querySelector( '.klientoora-card-overlay' );
	}

	function openTarget( trigger, targetId ) {
		var overlay = getOverlay();
		var target = document.getElementById( targetId );

		if ( ! target || ! overlay ) {
			return;
		}

		if ( closeTimer ) {
			window.clearTimeout( closeTimer );
			closeTimer = null;
		}

		if ( activeTarget && activeTarget !== target ) {
			activeTarget.classList.remove( 'is-open' );
			activeTarget.hidden = true;

			if ( activeTrigger ) {
				activeTrigger.setAttribute( 'aria-expanded', 'false' );
			}
		}

		activeTarget = target;
		activeTrigger = trigger;

		overlay.hidden = false;
		target.hidden = false;
		document.body.classList.add( 'klientoora-card-modal-open' );
		trigger.setAttribute( 'aria-expanded', 'true' );

		window.requestAnimationFrame( function () {
			target.classList.add( 'is-open' );
		} );

		var closeButton = target.querySelector( '[data-klientoora-card-close]' );

		if ( closeButton ) {
			closeButton.focus();
		}
	}

	function closeActive() {
		var overlay = getOverlay();

		if ( ! activeTarget ) {
			return;
		}

		activeTarget.classList.remove( 'is-open' );
		document.body.classList.remove( 'klientoora-card-modal-open' );

		if ( activeTrigger ) {
			activeTrigger.setAttribute( 'aria-expanded', 'false' );
			activeTrigger.focus();
		}

		closeTimer = window.setTimeout( function () {
			if ( overlay ) {
				overlay.hidden = true;
			}

			if ( activeTarget ) {
				activeTarget.hidden = true;
			}

			activeTarget = null;
			activeTrigger = null;
			closeTimer = null;
		}, 180 );
	}

	function getConfigValue( key, fallback ) {
		if ( 'undefined' === typeof window.klientooraCardPublic || ! window.klientooraCardPublic[ key ] ) {
			return fallback;
		}

		return window.klientooraCardPublic[ key ];
	}

	function setRegistrationError( form, message, debug ) {
		var error = form.querySelector( '[data-klientoora-card-registration-error]' );

		if ( ! error ) {
			return;
		}

		error.textContent = message || getConfigValue( 'errorText', 'Error' );

		if ( debug ) {
			var debugElement = document.createElement( 'small' );

			debugElement.textContent = debug;
			error.appendChild( document.createElement( 'br' ) );
			error.appendChild( debugElement );
		}

		error.hidden = false;
	}

	function clearRegistrationError( form ) {
		var error = form.querySelector( '[data-klientoora-card-registration-error]' );

		if ( ! error ) {
			return;
		}

		error.textContent = '';
		error.hidden = true;
	}

	function setRegistrationLoading( form, isLoading ) {
		var submitButton = form.querySelector( 'button[type="submit"]' );

		if ( ! submitButton ) {
			return;
		}

		if ( isLoading ) {
			submitButton.dataset.originalText = submitButton.textContent;
			submitButton.textContent = getConfigValue( 'submittingText', 'Submitting...' );
			submitButton.disabled = true;
			return;
		}

		if ( submitButton.dataset.originalText ) {
			submitButton.textContent = submitButton.dataset.originalText;
		}

		submitButton.disabled = false;
	}

	function showRegistrationSuccess( form, data ) {
		var modal = form.closest( '.klientoora-card-modal' );
		var title = modal ? modal.querySelector( '#klientoora-card-registration-modal-title' ) : null;
		var success = modal ? modal.querySelector( '[data-klientoora-card-registration-success]' ) : null;
		var message = data && data.message ? data.message : getConfigValue( 'successText', 'Registration completed.' );
		var passUrl = data && data.pass_url ? data.pass_url : '';
		var messageElement = document.createElement( 'p' );

		if ( ! success ) {
			return;
		}

		if ( title ) {
			title.textContent = getConfigValue( 'successTitle', 'Success' );
		}

		success.textContent = '';
		messageElement.textContent = message;
		success.appendChild( messageElement );

		if ( passUrl ) {
			var walletLink = document.createElement( 'a' );

			walletLink.className = 'klientoora-card-primary-action';
			walletLink.href = passUrl;
			walletLink.target = '_blank';
			walletLink.rel = 'noopener noreferrer';
			walletLink.textContent = getConfigValue( 'walletText', 'Open wallet' );
			success.appendChild( walletLink );
		}

		form.hidden = true;
		success.hidden = false;
		success.focus();
	}

	function getCheckoutBox( element ) {
		return element ? element.closest( '[data-klientoora-card-checkout-redemption]' ) : null;
	}

	function setCheckoutNotice( box, message, isError ) {
		var notice = box ? box.querySelector( '[data-klientoora-card-redeem-notice]' ) : null;

		if ( ! notice ) {
			return;
		}

		notice.textContent = message || '';
		notice.classList.toggle( 'is-error', !! isError );
		notice.hidden = ! message;
	}

	function setCheckoutButtonLoading( button, isLoading, loadingText ) {
		var state = button ? button.querySelector( '[data-klientoora-card-points-state]' ) : null;

		if ( ! button ) {
			return;
		}

		if ( isLoading ) {
			if ( state ) {
				state.dataset.originalText = state.textContent;
				state.textContent = loadingText;
			} else {
				button.dataset.originalText = button.textContent;
				button.textContent = loadingText;
			}
			button.disabled = true;
			return;
		}

		if ( state ) {
			state.textContent = button.classList.contains( 'is-selected' )
				? getConfigValue( 'selectedCouponText', 'Selected' )
				: getConfigValue( 'selectCouponText', 'Select' );
		} else if ( button.dataset.originalText ) {
			button.textContent = button.dataset.originalText;
		}

		button.disabled = false;
	}

	function refreshWooBlocksCheckout() {
		var cartStore = window.wc && window.wc.wcBlocksData
			? window.wc.wcBlocksData.cartStore || window.wc.wcBlocksData.CART_STORE_KEY || 'wc/store/cart'
			: 'wc/store/cart';
		var hasBlocksCheckout = !! document.querySelector( '.wp-block-woocommerce-checkout, .wc-block-checkout' );
		var cartDispatch = null;

		if ( ! hasBlocksCheckout ) {
			return false;
		}

		if ( window.wp && window.wp.data && window.wp.data.dispatch ) {
			try {
				cartDispatch = window.wp.data.dispatch( cartStore );
			} catch ( error ) {
				cartDispatch = null;
			}
		}

		if ( cartDispatch && 'function' === typeof cartDispatch.invalidateResolutionForStore ) {
			cartDispatch.invalidateResolutionForStore();
		} else if ( cartDispatch && 'function' === typeof cartDispatch.invalidateResolution ) {
			cartDispatch.invalidateResolution( 'getCartData', [] );
		}

		document.body.dispatchEvent(
			new window.CustomEvent( 'wc-blocks_added_to_cart', {
				bubbles: true,
				cancelable: true,
				detail: {
					preserveCartData: false
				}
			} )
		);

		window.dispatchEvent(
			new window.CustomEvent( 'wc-blocks_store_sync_required', {
				detail: {
					type: 'from_@wordpress/data'
				}
			} )
		);

		return true;
	}

	function refreshWooCheckout() {
		refreshWooBlocksCheckout();

		if ( window.jQuery && window.jQuery.fn ) {
			window.jQuery( document.body ).trigger( 'update_checkout' );
		}
	}

	function moveCheckoutFallbackBox() {
		var fallback = document.querySelector( '[data-klientoora-card-checkout-redemption-fallback]' );
		var target = document.querySelector( '.wp-block-woocommerce-checkout, .wc-block-checkout, form.checkout' );

		if ( ! fallback || ! target || ! target.parentNode ) {
			return;
		}

		target.parentNode.insertBefore( fallback, target );
	}

	function updateCheckoutRedeemedMessage( box, message, isApplied ) {
		var redeemedMessage = box ? box.querySelector( '[data-klientoora-card-redeemed-message]' ) : null;
		var pointsCard = box ? box.querySelector( '[data-klientoora-card-toggle-points]' ) : null;
		var pointsState = pointsCard ? pointsCard.querySelector( '[data-klientoora-card-points-state]' ) : null;

		if ( redeemedMessage ) {
			redeemedMessage.textContent = message || '';
			redeemedMessage.hidden = ! isApplied || ! message;
		}

		if ( pointsCard ) {
			pointsCard.classList.toggle( 'is-selected', isApplied );
			pointsCard.setAttribute( 'aria-pressed', isApplied ? 'true' : 'false' );
		}

		if ( pointsState ) {
			pointsState.textContent = isApplied
				? getConfigValue( 'selectedCouponText', 'Selected' )
				: getConfigValue( 'selectCouponText', 'Select' );
		}
	}

	function normalizeCouponCode( couponCode ) {
		return ( couponCode || '' ).toString().toLowerCase();
	}

	function updateSelectedCheckoutCoupon( box, couponCode ) {
		var coupons = box ? box.querySelectorAll( '[data-klientoora-card-apply-coupon]' ) : [];
		var selectedCouponCode = normalizeCouponCode( couponCode );

		coupons.forEach( function ( coupon ) {
			var isSelected = normalizeCouponCode( coupon.dataset.couponCode ) === selectedCouponCode;
			var state = coupon.querySelector( '[data-klientoora-card-coupon-state]' );

			coupon.classList.toggle( 'is-selected', isSelected );
			coupon.setAttribute( 'aria-pressed', isSelected ? 'true' : 'false' );

			if ( state ) {
				state.textContent = isSelected
					? getConfigValue( 'selectedCouponText', 'Selected' )
					: getConfigValue( 'selectCouponText', 'Select' );
			}
		} );
	}

	function setCheckoutCouponLoading( button, isLoading ) {
		var state = button ? button.querySelector( '[data-klientoora-card-coupon-state]' ) : null;

		if ( ! button || ! state ) {
			return;
		}

		if ( isLoading ) {
			state.dataset.originalText = state.textContent;
			state.textContent = getConfigValue( 'applyCouponLoadingText', 'Applying...' );
			button.disabled = true;
			return;
		}

		state.textContent = button.classList.contains( 'is-selected' )
			? getConfigValue( 'selectedCouponText', 'Selected' )
			: getConfigValue( 'selectCouponText', 'Select' );

		button.disabled = false;
	}

	function sendCheckoutPointsRequest( button, action, loadingText ) {
		var box = getCheckoutBox( button );
		var formData = new window.FormData();

		if ( ! box ) {
			return;
		}

		formData.append( 'action', action );
		formData.append( 'nonce', getConfigValue( 'redeemPointsNonce', '' ) );

		setCheckoutNotice( box, '', false );
		setCheckoutButtonLoading( button, true, loadingText );

		window.fetch( getConfigValue( 'ajaxUrl', '' ), {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json'
			}
		} )
			.then( function ( response ) {
				return response.text().then( function ( text ) {
					var json = null;

					try {
						json = JSON.parse( text );
					} catch ( error ) {
						throw new Error( getConfigValue( 'redeemPointsErrorText', 'Error' ) );
					}

					if ( ! response.ok || ! json || ! json.success ) {
						throw new Error(
							json && json.data && json.data.message
								? json.data.message
								: getConfigValue( 'redeemPointsErrorText', 'Error' )
						);
					}

					return json.data || {};
				} );
			} )
			.then( function ( data ) {
				var isApplied = 0 < parseInt( data.redeemed_points || 0, 10 );

				updateCheckoutRedeemedMessage( box, data.message || '', isApplied );
				if ( isApplied ) {
					updateSelectedCheckoutCoupon( box, '' );
				}
				setCheckoutNotice( box, data.message || '', false );
				refreshWooCheckout();
			} )
			.catch( function ( error ) {
				setCheckoutNotice(
					box,
					error.message || getConfigValue( 'redeemPointsErrorText', 'Error' ),
					true
				);
			} )
			.finally( function () {
				setCheckoutButtonLoading( button, false, loadingText );
			} );
	}

	function sendCheckoutCouponRequest( button ) {
		var box = getCheckoutBox( button );
		var formData = new window.FormData();
		var couponCode = button ? button.dataset.couponCode || '' : '';

		if ( ! box || ! couponCode ) {
			return;
		}

		formData.append( 'action', 'klientoora_card_apply_loyalty_coupon' );
		formData.append( 'nonce', getConfigValue( 'applyCouponNonce', '' ) );
		formData.append( 'coupon_code', couponCode );

		setCheckoutNotice( box, '', false );
		setCheckoutCouponLoading( button, true );

		window.fetch( getConfigValue( 'ajaxUrl', '' ), {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json'
			}
		} )
			.then( function ( response ) {
				return response.text().then( function ( text ) {
					var json = null;

					try {
						json = JSON.parse( text );
					} catch ( error ) {
						throw new Error( getConfigValue( 'applyCouponErrorText', 'Error' ) );
					}

					if ( ! response.ok || ! json || ! json.success ) {
						throw new Error(
							json && json.data && json.data.message
								? json.data.message
								: getConfigValue( 'applyCouponErrorText', 'Error' )
						);
					}

					return json.data || {};
				} );
			} )
			.then( function ( data ) {
				var selectedCouponCode = 'string' === typeof data.coupon_code ? data.coupon_code : couponCode;

				updateSelectedCheckoutCoupon( box, selectedCouponCode );
				updateCheckoutRedeemedMessage(
					box,
					data.points_message || '',
					'points' === data.selected_mode
				);
				setCheckoutNotice( box, data.message || '', false );
				refreshWooCheckout();
			} )
			.catch( function ( error ) {
				updateSelectedCheckoutCoupon( box, '' );
				updateCheckoutRedeemedMessage( box, '', true );
				setCheckoutNotice(
					box,
					error.message || getConfigValue( 'applyCouponErrorText', 'Error' ),
					true
				);
			} )
			.finally( function () {
				setCheckoutCouponLoading( button, false );
			} );
	}

	function getProductRedemptionBox( element ) {
		return element ? element.closest( '[data-klientoora-card-product-redemptions]' ) : null;
	}

	function setProductRedemptionNotice( box, message, isError ) {
		var notice = box ? box.querySelector( '[data-klientoora-card-product-notice]' ) : null;

		if ( ! notice ) {
			return;
		}

		notice.textContent = message || '';
		notice.classList.toggle( 'is-error', !! isError );
		notice.hidden = ! message;
	}

	function setProductRedemptionLoading( button, isLoading ) {
		if ( ! button ) {
			return;
		}

		if ( isLoading ) {
			button.dataset.originalText = button.textContent;
			button.textContent = getConfigValue( 'redeemProductLoadingText', 'Redeeming...' );
			button.disabled = true;
			return;
		}

		if ( button.dataset.originalText ) {
			button.textContent = button.dataset.originalText;
		}

		button.disabled = false;
	}

	function updateMemberPointsBalance( pointsBalance ) {
		var balance = document.querySelector( '[data-klientoora-card-member-points-balance]' );

		if ( balance && 'undefined' !== typeof pointsBalance ) {
			balance.textContent = pointsBalance.toLocaleString();
		}
	}

	function refreshMemberPointsBalance() {
		var formData = new window.FormData();

		if ( ! document.querySelector( '[data-klientoora-card-member-points-balance]' ) ) {
			return;
		}

		formData.append( 'action', 'klientoora_card_get_points_balance' );
		formData.append( 'nonce', getConfigValue( 'pointsBalanceNonce', '' ) );

		window.fetch( getConfigValue( 'ajaxUrl', '' ), {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json'
			}
		} )
			.then( function ( response ) {
				return response.text().then( function ( text ) {
					var json = null;

					try {
						json = JSON.parse( text );
					} catch ( error ) {
						return null;
					}

					return response.ok && json && json.success ? json.data || {} : null;
				} );
			} )
			.then( function ( data ) {
				var balance = document.querySelector( '[data-klientoora-card-member-points-balance]' );

				if ( ! data || ! balance ) {
					return;
				}

				if ( data.points_balance_formatted ) {
					balance.textContent = data.points_balance_formatted;
					return;
				}

				updateMemberPointsBalance( parseInt( data.points_balance || 0, 10 ) );
			} )
			.catch( function () {} );
	}

	function sendProductRedemptionRequest( button ) {
		var box = getProductRedemptionBox( button );
		var productId = button ? button.dataset.productId || '' : '';
		var formData = new window.FormData();

		if ( ! box || ! productId ) {
			return;
		}

		formData.append( 'action', 'klientoora_card_redeem_product' );
		formData.append( 'nonce', getConfigValue( 'redeemProductNonce', '' ) );
		formData.append( 'product_id', productId );

		setProductRedemptionNotice( box, '', false );
		setProductRedemptionLoading( button, true );

		window.fetch( getConfigValue( 'ajaxUrl', '' ), {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json'
			}
		} )
			.then( function ( response ) {
				return response.text().then( function ( text ) {
					var json = null;

					try {
						json = JSON.parse( text );
					} catch ( error ) {
						throw new Error( getConfigValue( 'redeemProductErrorText', 'Error' ) );
					}

					if ( ! response.ok || ! json || ! json.success ) {
						throw new Error(
							json && json.data && json.data.message
								? json.data.message
								: getConfigValue( 'redeemProductErrorText', 'Error' )
						);
					}

					return json.data || {};
				} );
			} )
			.then( function ( data ) {
				var card = button.closest( '.klientoora-card-member-product' );

				setProductRedemptionNotice( box, data.message || '', false );
				updateMemberPointsBalance( parseInt( data.points_balance || 0, 10 ) );

				if ( card ) {
					card.classList.add( 'is-disabled' );
				}

				button.textContent = getConfigValue( 'redeemProductDoneText', 'Redeemed' );
				button.disabled = true;
			} )
			.catch( function ( error ) {
				setProductRedemptionNotice(
					box,
					error.message || getConfigValue( 'redeemProductErrorText', 'Error' ),
					true
				);
				setProductRedemptionLoading( button, false );
			} );
	}

	function handleRegistrationSubmit( event ) {
		var form = event.target;

		if ( ! form.matches( '[data-klientoora-card-registration-form]' ) ) {
			return;
		}

		var formData = new window.FormData( form );

		event.preventDefault();
		clearRegistrationError( form );
		setRegistrationLoading( form, true );

		window.fetch( getConfigValue( 'ajaxUrl', form.action ), {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json'
			}
		} )
			.then( function ( response ) {
				return response.text().then( function ( text ) {
					var json = null;

					try {
						json = JSON.parse( text );
					} catch ( error ) {
						throw new Error( getConfigValue( 'errorText', 'Error' ) );
					}

					if ( ! response.ok && json && ! json.success ) {
						throw new Error(
							json.data && json.data.message
								? json.data.message
								: getConfigValue( 'errorText', 'Error' )
						);
					}

					return json;
				} );
			} )
			.then( function ( response ) {
				if ( ! response || ! response.success ) {
					var ajaxError = new Error(
						response && response.data && response.data.message
							? response.data.message
							: getConfigValue( 'errorText', 'Error' )
					);

					ajaxError.debug = response && response.data && response.data.debug ? response.data.debug : '';
					throw ajaxError;
				}

				showRegistrationSuccess( form, response.data || {} );
			} )
			.catch( function ( error ) {
				setRegistrationError( form, error.message || getConfigValue( 'errorText', 'Error' ), error.debug || '' );
			} )
			.finally( function () {
				setRegistrationLoading( form, false );
			} );
	}

	document.addEventListener( 'click', function ( event ) {
		var modalTrigger = event.target.closest( '[data-klientoora-card-open-modal]' );
		var panelTrigger = event.target.closest( '[data-klientoora-card-open-panel]' );
		var registerTrigger = event.target.closest( '[data-klientoora-card-open-register]' );
		var closeTrigger = event.target.closest( '[data-klientoora-card-close]' );
		var togglePointsTrigger = event.target.closest( '[data-klientoora-card-toggle-points]' );
		var redeemPointsTrigger = event.target.closest( '[data-klientoora-card-redeem-points]' );
		var clearRedeemedPointsTrigger = event.target.closest( '[data-klientoora-card-clear-redeemed-points]' );
		var applyCouponTrigger = event.target.closest( '[data-klientoora-card-apply-coupon]' );
		var redeemProductTrigger = event.target.closest( '[data-klientoora-card-redeem-product]' );

		if ( modalTrigger ) {
			openTarget( modalTrigger, modalTrigger.getAttribute( 'aria-controls' ) );
			return;
		}

		if ( panelTrigger ) {
			openTarget( panelTrigger, panelTrigger.getAttribute( 'aria-controls' ) );
			return;
		}

		if ( registerTrigger ) {
			openTarget( registerTrigger, registerTrigger.getAttribute( 'aria-controls' ) );
			return;
		}

		if ( closeTrigger ) {
			closeActive();
			return;
		}

		if ( togglePointsTrigger ) {
			event.preventDefault();

			if ( togglePointsTrigger.classList.contains( 'is-selected' ) ) {
				return;
			}

			sendCheckoutPointsRequest(
				togglePointsTrigger,
				'klientoora_card_redeem_points',
				getConfigValue( 'redeemPointsLoadingText', 'Loading...' )
			);
			return;
		}

		if ( redeemPointsTrigger ) {
			event.preventDefault();
			sendCheckoutPointsRequest(
				redeemPointsTrigger,
				'klientoora_card_redeem_points',
				getConfigValue( 'redeemPointsLoadingText', 'Loading...' )
			);
			return;
		}

		if ( clearRedeemedPointsTrigger ) {
			event.preventDefault();
			sendCheckoutPointsRequest(
				clearRedeemedPointsTrigger,
				'klientoora_card_clear_redeemed_points',
				getConfigValue( 'clearPointsLoadingText', 'Loading...' )
			);
			return;
		}

		if ( applyCouponTrigger ) {
			event.preventDefault();
			sendCheckoutCouponRequest( applyCouponTrigger );
			return;
		}

		if ( redeemProductTrigger ) {
			event.preventDefault();
			sendProductRedemptionRequest( redeemProductTrigger );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			closeActive();
		}
	} );

	document.addEventListener( 'submit', handleRegistrationSubmit );

	if ( window.jQuery && window.jQuery.fn ) {
		window.jQuery( document.body ).on( 'removed_from_cart wc_fragments_refreshed updated_cart_totals', refreshMemberPointsBalance );
	}

	document.body.addEventListener( 'wc-blocks_removed_from_cart', refreshMemberPointsBalance );

	document.addEventListener( 'DOMContentLoaded', function () {
		var params = new window.URLSearchParams( window.location.search );
		var trigger = document.querySelector( '[data-klientoora-card-open-register]' );

		moveCheckoutFallbackBox();

		if ( ! trigger || ! params.has( 'klientoora_card_status' ) ) {
			return;
		}

		openTarget( trigger, trigger.getAttribute( 'aria-controls' ) );
	} );
}() );
