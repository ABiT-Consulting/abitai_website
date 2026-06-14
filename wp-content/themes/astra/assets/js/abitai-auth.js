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

	document.addEventListener( 'DOMContentLoaded', function () {
		var autofocusTarget = document.querySelector( '[data-auth-autofocus]' );
		if ( autofocusTarget ) {
			autofocusTarget.focus();
		}

		document.querySelectorAll( '[data-auth-signin-form]' ).forEach( initSignInForm );
	} );
}() );
