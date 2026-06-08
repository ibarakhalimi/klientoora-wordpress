( function () {
	'use strict';

	var fieldMap = {
		amount: 'amount',
		couponCode: 'coupon_code',
		description: 'description',
		discountType: 'discount_type',
		expiryDate: 'expiry_date',
		maximumSpend: 'maximum_spend',
		minimumSpend: 'minimum_spend',
		usageLimit: 'usage_limit',
		usageLimitPerUser: 'usage_limit_per_user'
	};

	function setFieldValue( form, fieldName, value ) {
		var field = form.querySelector( '[name="' + fieldName + '"]' );

		if ( ! field ) {
			return;
		}

		field.value = value;
		field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function setCheckboxValue( form, fieldName, value ) {
		var field = form.querySelector( '[name="' + fieldName + '"]' );

		if ( ! field ) {
			return;
		}

		field.checked = 'yes' === value;
		field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function applyTemplateToForm( template, form ) {
		Object.keys( fieldMap ).forEach( function ( key ) {
			setFieldValue( form, fieldMap[ key ], template.dataset[ 'template' + key.charAt( 0 ).toUpperCase() + key.slice( 1 ) ] || '' );
		} );

		setCheckboxValue( form, 'free_shipping', template.dataset.templateFreeShipping || 'no' );
		setCheckboxValue( form, 'members_only', template.dataset.templateMembersOnly || 'no' );
		setCheckboxValue( form, 'show_in_popup', template.dataset.templateShowInPopup || 'no' );
	}

	function selectTemplate( template ) {
		var templates = document.querySelectorAll( '[data-klientoora-coupon-template]' );

		templates.forEach( function ( currentTemplate ) {
			currentTemplate.classList.remove( 'is-selected' );
			currentTemplate.setAttribute( 'aria-pressed', 'false' );
		} );

		template.classList.add( 'is-selected' );
		template.setAttribute( 'aria-pressed', 'true' );
	}

	document.addEventListener( 'click', function ( event ) {
		var template = event.target.closest( '[data-klientoora-coupon-template]' );
		var form = document.querySelector( '.klientoora-card-coupon-form-card form' );

		if ( ! template || ! form ) {
			return;
		}

		event.preventDefault();
		applyTemplateToForm( template, form );
		selectTemplate( template );
	} );
}() );
