( function () {
	'use strict';

	function setFieldError( field, messageNode, input, hasError ) {
		field.classList.toggle( 'is-error', hasError );
		input.setAttribute( 'aria-invalid', hasError ? 'true' : 'false' );
		if ( messageNode ) {
			messageNode.hidden = ! hasError;
		}
	}

	function isValidEmail( value ) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value );
	}

	function isWeakPassword( value ) {
		var commonPasswords = [
			'password',
			'password123',
			'password123!',
			'123456789012',
			'qwerty123456',
			'admin1234567',
			'welcome12345'
		];

		return value.length < 12 || value.length > 128 || commonPasswords.indexOf( value.toLowerCase() ) !== -1;
	}

	function lockLinks( form, shouldLock ) {
		var panel = form.closest( '.abit-auth-route-card' );
		if ( ! panel ) {
			return;
		}

		panel.querySelectorAll( '[data-auth-lockable]' ).forEach( function ( link ) {
			if ( shouldLock ) {
				link.setAttribute( 'aria-disabled', 'true' );
				link.setAttribute( 'tabindex', '-1' );
			} else {
				link.removeAttribute( 'aria-disabled' );
				link.removeAttribute( 'tabindex' );
			}
		} );
	}

	function initSignInForm( form ) {
		var email = form.querySelector( '#abit-auth-email' );
		var password = form.querySelector( '#abit-auth-password' );
		var emailField = form.querySelector( '[data-auth-field="email"]' );
		var passwordField = form.querySelector( '[data-auth-field="password"]' );
		var emailError = form.querySelector( '#abit-auth-email-error' );
		var passwordError = form.querySelector( '#abit-auth-password-error' );
		var submit = form.querySelector( '[data-auth-submit]' );
		var success = document.querySelector( '.abit-auth-signin-success' );
		var defaultLabel = submit ? submit.textContent : '';
		var loadingLabel = submit ? submit.getAttribute( 'data-loading-label' ) : '';

		function validate() {
			var emailInvalid = ! isValidEmail( email.value.trim() );
			var passwordInvalid = '' === password.value;

			setFieldError( emailField, emailError, email, emailInvalid );
			setFieldError( passwordField, passwordError, password, passwordInvalid );

			if ( emailInvalid ) {
				email.focus();
				return false;
			}

			if ( passwordInvalid ) {
				password.focus();
				return false;
			}

			return true;
		}

		[ email, password ].forEach( function ( input ) {
			input.addEventListener( 'input', function () {
				var field = input === email ? emailField : passwordField;
				var error = input === email ? emailError : passwordError;
				input.removeAttribute( 'aria-invalid' );
				field.classList.remove( 'is-error' );
				if ( error ) {
					error.hidden = true;
				}
			} );
		} );

		form.addEventListener( 'submit', function ( event ) {
			if ( ! validate() ) {
				event.preventDefault();
				return;
			}

			if ( submit ) {
				submit.disabled = true;
				submit.classList.add( 'is-loading' );
				submit.setAttribute( 'aria-busy', 'true' );
				submit.textContent = loadingLabel || defaultLabel;
			}

			form.querySelectorAll( 'input' ).forEach( function ( input ) {
				input.readOnly = true;
			} );

			lockLinks( form, true );

			if ( success ) {
				success.hidden = false;
			}
		} );
	}

	function initSignupForm( form ) {
		var name = form.querySelector( '#abit-auth-signup-name' );
		var email = form.querySelector( '#abit-auth-signup-email' );
		var password = form.querySelector( '#abit-auth-signup-password' );
		var confirmPassword = form.querySelector( '#abit-auth-signup-confirm-password' );
		var consent = form.querySelector( '#abit-auth-signup-consent' );
		var submit = form.querySelector( '[data-auth-submit]' );
		var success = document.querySelector( '.abit-auth-signup-success' );
		var defaultLabel = submit ? submit.textContent : '';
		var loadingLabel = submit ? submit.getAttribute( 'data-loading-label' ) : '';

		var fields = {
			full_name: {
				input: name,
				field: form.querySelector( '[data-auth-field="full_name"]' ),
				error: form.querySelector( '#abit-auth-signup-name-error' )
			},
			email: {
				input: email,
				field: form.querySelector( '[data-auth-field="email"]' ),
				error: form.querySelector( '#abit-auth-signup-email-error' )
			},
			password: {
				input: password,
				field: form.querySelector( '[data-auth-field="password"]' ),
				error: form.querySelector( '#abit-auth-signup-password-error' )
			},
			confirm_password: {
				input: confirmPassword,
				field: form.querySelector( '[data-auth-field="confirm_password"]' ),
				error: form.querySelector( '#abit-auth-signup-confirm-password-error' )
			},
			consent: {
				input: consent,
				field: form.querySelector( '[data-auth-field="consent"]' ),
				error: form.querySelector( '#abit-auth-signup-consent-error' )
			}
		};

		function markError( key, hasError ) {
			setFieldError( fields[ key ].field, fields[ key ].error, fields[ key ].input, hasError );
		}

		function validate() {
			var invalid = {
				full_name: '' === name.value.trim(),
				email: ! isValidEmail( email.value.trim() ),
				password: isWeakPassword( password.value ),
				confirm_password: '' === confirmPassword.value || password.value !== confirmPassword.value,
				consent: ! consent.checked
			};
			var firstInvalid = null;

			Object.keys( invalid ).forEach( function ( key ) {
				markError( key, invalid[ key ] );
				if ( invalid[ key ] && ! firstInvalid ) {
					firstInvalid = fields[ key ].input;
				}
			} );

			if ( firstInvalid ) {
				firstInvalid.focus();
				return false;
			}

			return true;
		}

		Object.keys( fields ).forEach( function ( key ) {
			var field = fields[ key ];
			var eventName = 'checkbox' === field.input.type ? 'change' : 'input';

			field.input.addEventListener( eventName, function () {
				field.input.removeAttribute( 'aria-invalid' );
				field.field.classList.remove( 'is-error' );
				if ( field.error ) {
					field.error.hidden = true;
				}
			} );
		} );

		form.addEventListener( 'submit', function ( event ) {
			if ( ! validate() ) {
				event.preventDefault();
				return;
			}

			if ( submit ) {
				submit.disabled = true;
				submit.classList.add( 'is-loading' );
				submit.setAttribute( 'aria-busy', 'true' );
				submit.textContent = loadingLabel || defaultLabel;
			}

			form.querySelectorAll( 'input' ).forEach( function ( input ) {
				if ( 'checkbox' !== input.type && 'hidden' !== input.type ) {
					input.readOnly = true;
				}
			} );

			lockLinks( form, true );

			if ( success ) {
				success.hidden = false;
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var autofocusTarget = document.querySelector( '[data-auth-autofocus]' );
		if ( autofocusTarget ) {
			autofocusTarget.focus();
		}

		document.querySelectorAll( '[data-auth-signin-form]' ).forEach( initSignInForm );
		document.querySelectorAll( '[data-auth-signup-form]' ).forEach( initSignupForm );
	} );
}() );
