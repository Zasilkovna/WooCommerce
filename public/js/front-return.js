( function () {
	'use strict';

	var settings = window.packeteryReturnFormSettings;
	if ( ! settings ) {
		return;
	}

	document.querySelectorAll( '.packetery-order-return-form' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var button = form.querySelector( 'button[type="submit"]' );
			var message = form.querySelector( '.packetery-order-return-message' );

			var body = {};
			form.querySelectorAll( 'input[name], textarea[name], select[name]' ).forEach( function ( field ) {
				body[ field.name ] = field.value;
			} );

			if ( button ) {
				button.disabled = true;
			}
			message.textContent = settings.translations.sending;

			fetch( settings.createUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': settings.nonce
				},
				body: JSON.stringify( body )
			} ).then( function ( response ) {
				return response.json().then( function ( data ) {
					return { ok: response.ok, data: data };
				} );
			} ).then( function ( result ) {
				if ( ! result.ok ) {
					message.textContent = ( result.data && result.data.message ) || settings.translations.error;
					if ( button ) {
						button.disabled = false;
					}

					return;
				}

				var link = result.data.trackingUrl
					? '<a href="' + result.data.trackingUrl + '" target="_blank">' + result.data.barcode + '</a>'
					: result.data.barcode;
				message.innerHTML = settings.translations.created + ' ' + link;
				form.querySelectorAll( '.form-row' ).forEach( function ( row ) {
					row.setAttribute( 'hidden', 'hidden' );
				} );
				if ( button ) {
					button.setAttribute( 'hidden', 'hidden' );
				}
			} ).catch( function () {
				message.textContent = settings.translations.error;
				if ( button ) {
					button.disabled = false;
				}
			} );
		} );
	} );
}() );
