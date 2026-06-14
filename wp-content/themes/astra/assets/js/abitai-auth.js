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

	function hasUnsafeText( value ) {
		return /[\x00-\x08\x0B\x0C\x0E-\x1F\x7F<>]/.test( value ) || /(https?:\/\/|www\.)/i.test( value );
	}

	function isValidPhone( value ) {
		return '' === value.trim() || ( value.length <= 40 && /^[0-9+().\-\s]+$/.test( value ) );
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
		var accountStep = form.querySelector( '[data-auth-signup-step="account"]' );
		var companyStep = form.querySelector( '[data-auth-signup-step="company"]' );
		var modulesStep = form.querySelector( '[data-auth-signup-step="modules"]' );
		var nextStep = form.querySelector( '[data-auth-next-step]' );
		var nextCompanyStep = form.querySelector( '[data-auth-next-company-step]' );
		var prevStep = form.querySelector( '[data-auth-prev-step]' );
		var prevCompanyStep = form.querySelector( '[data-auth-prev-company-step]' );
		var accountStepper = document.querySelector( '[data-auth-stepper-item="account"]' );
		var companyStepper = document.querySelector( '[data-auth-stepper-item="company"]' );
		var modulesStepper = document.querySelector( '[data-auth-stepper-item="modules"]' );
		var submit = form.querySelector( '[data-auth-submit]' );
		var success = document.querySelector( '.abit-auth-signup-success' );
		var defaultLabel = submit ? submit.textContent : '';
		var loadingLabel = submit ? submit.getAttribute( 'data-loading-label' ) : '';
		var storageKey = 'abitai.signup.companyStepDraft';
		var moduleInputs = Array.prototype.slice.call( form.querySelectorAll( 'input[name="erp_module_interest[]"]' ) );

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
			},
			company_name: {
				input: form.querySelector( '#abit-auth-company-name' ),
				field: form.querySelector( '[data-auth-field="company_name"]' ),
				error: form.querySelector( '#abit-auth-company-name-error' )
			},
			country_region: {
				input: form.querySelector( '#abit-auth-country-region' ),
				field: form.querySelector( '[data-auth-field="country_region"]' ),
				error: form.querySelector( '#abit-auth-country-region-error' )
			},
			job_title: {
				input: form.querySelector( '#abit-auth-job-title' ),
				field: form.querySelector( '[data-auth-field="job_title"]' ),
				error: form.querySelector( '#abit-auth-job-title-error' )
			},
			company_size: {
				input: form.querySelector( '#abit-auth-company-size' ),
				field: form.querySelector( '[data-auth-field="company_size"]' ),
				error: form.querySelector( '#abit-auth-company-size-error' )
			},
			industry: {
				input: form.querySelector( '#abit-auth-industry' ),
				field: form.querySelector( '[data-auth-field="industry"]' ),
				error: form.querySelector( '#abit-auth-industry-error' )
			},
			business_description: {
				input: form.querySelector( '#abit-auth-business-description' ),
				field: form.querySelector( '[data-auth-field="business_description"]' ),
				error: form.querySelector( '#abit-auth-business-description-error' )
			},
			phone: {
				input: form.querySelector( '#abit-auth-phone' ),
				field: form.querySelector( '[data-auth-field="phone"]' ),
				error: form.querySelector( '#abit-auth-phone-error' )
			},
			erp_module_interest: {
				input: moduleInputs[ 0 ],
				inputs: moduleInputs,
				field: form.querySelector( '[data-auth-field="erp_module_interest"]' ),
				error: form.querySelector( '#abit-auth-module-interest-error' )
			}
		};

		function markError( key, hasError ) {
			if ( 'erp_module_interest' === key ) {
				fields[ key ].field.classList.toggle( 'is-error', hasError );
				fields[ key ].inputs.forEach( function ( input ) {
					input.setAttribute( 'aria-invalid', hasError ? 'true' : 'false' );
				} );
				if ( fields[ key ].error ) {
					fields[ key ].error.hidden = ! hasError;
				}
				return;
			}

			setFieldError( fields[ key ].field, fields[ key ].error, fields[ key ].input, hasError );
		}

		function focusFirstInvalid( invalid ) {
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

		function validateAccountStep() {
			var invalid = {
				full_name: '' === name.value.trim(),
				email: ! isValidEmail( email.value.trim() ),
				password: isWeakPassword( password.value ),
				confirm_password: '' === confirmPassword.value || password.value !== confirmPassword.value,
				consent: ! consent.checked
			};

			return focusFirstInvalid( invalid );
		}

		function validateCompanyStep() {
			var companyName = fields.company_name.input.value.trim();
			var jobTitle = fields.job_title.input.value.trim();
			var description = fields.business_description.input.value.trim();
			var invalid = {
				company_name: companyName.length < 2 || companyName.length > 160 || hasUnsafeText( companyName ),
				country_region: '' === fields.country_region.input.value,
				job_title: jobTitle.length < 2 || jobTitle.length > 120 || hasUnsafeText( jobTitle ),
				company_size: '' === fields.company_size.input.value,
				industry: '' === fields.industry.input.value,
				business_description: description.length < 20 || description.length > 1000 || hasUnsafeText( description ),
				phone: ! isValidPhone( fields.phone.input.value )
			};

			return focusFirstInvalid( invalid );
		}

		function validateModulesStep() {
			var invalid = {
				erp_module_interest: ! fields.erp_module_interest.inputs.some( function ( input ) {
					return input.checked;
				} )
			};

			return focusFirstInvalid( invalid );
		}

		function setStep( stepName ) {
			var isAccount = 'account' === stepName;
			var isCompany = 'company' === stepName;
			var isModules = 'modules' === stepName;

			if ( accountStep && companyStep && modulesStep ) {
				accountStep.hidden = ! isAccount;
				companyStep.hidden = ! isCompany;
				modulesStep.hidden = ! isModules;
			}

			if ( accountStepper && companyStepper && modulesStepper ) {
				accountStepper.classList.toggle( 'is-active', isAccount );
				accountStepper.classList.toggle( 'is-complete', isCompany || isModules );
				companyStepper.classList.toggle( 'is-active', isCompany );
				companyStepper.classList.toggle( 'is-complete', isModules );
				modulesStepper.classList.toggle( 'is-active', isModules );

				if ( isAccount ) {
					accountStepper.setAttribute( 'aria-current', 'step' );
				} else {
					accountStepper.removeAttribute( 'aria-current' );
				}

				if ( isCompany ) {
					companyStepper.setAttribute( 'aria-current', 'step' );
				} else {
					companyStepper.removeAttribute( 'aria-current' );
				}

				if ( isModules ) {
					modulesStepper.setAttribute( 'aria-current', 'step' );
				} else {
					modulesStepper.removeAttribute( 'aria-current' );
				}
			}
		}

		function getDraftFields() {
			return Object.keys( fields ).filter( function ( key ) {
				return fields[ key ].input && 'password' !== key && 'confirm_password' !== key;
			} );
		}

		function saveDraft() {
			var draft = {};

			getDraftFields().forEach( function ( key ) {
				var input = fields[ key ].input;

				if ( 'erp_module_interest' === key ) {
					draft[ key ] = fields[ key ].inputs.filter( function ( moduleInput ) {
						return moduleInput.checked;
					} ).map( function ( moduleInput ) {
						return moduleInput.value;
					} );
					return;
				}

				draft[ key ] = 'checkbox' === input.type ? input.checked : input.value;
			} );

			try {
				window.sessionStorage.setItem( storageKey, JSON.stringify( draft ) );
			} catch ( error ) {}
		}

		function restoreDraft() {
			var draft;

			try {
				draft = JSON.parse( window.sessionStorage.getItem( storageKey ) || '{}' );
			} catch ( error ) {
				draft = {};
			}

			getDraftFields().forEach( function ( key ) {
				var input = fields[ key ].input;

				if ( ! Object.prototype.hasOwnProperty.call( draft, key ) ) {
					return;
				}

				if ( 'erp_module_interest' === key ) {
					fields[ key ].inputs.forEach( function ( moduleInput ) {
						if ( moduleInput.checked ) {
							return;
						}
						moduleInput.checked = Array.isArray( draft[ key ] ) && draft[ key ].indexOf( moduleInput.value ) !== -1;
						moduleInput.closest( '.abit-auth-module-option' ).classList.toggle( 'is-selected', moduleInput.checked );
					} );
				} else if ( 'checkbox' === input.type ) {
					if ( input.checked ) {
						return;
					}
					input.checked = !! draft[ key ];
				} else if ( '' === input.value ) {
					input.value = draft[ key ];
				}
			} );
		}

		Object.keys( fields ).forEach( function ( key ) {
			var field = fields[ key ];
			var eventName;

			if ( ! field.input ) {
				return;
			}

			eventName = 'checkbox' === field.input.type || 'SELECT' === field.input.tagName ? 'change' : 'input';

			( field.inputs || [ field.input ] ).forEach( function ( input ) {
				input.addEventListener( eventName, function () {
					( field.inputs || [ field.input ] ).forEach( function ( fieldInput ) {
						fieldInput.removeAttribute( 'aria-invalid' );
					} );

					field.field.classList.remove( 'is-error' );
					if ( field.error ) {
						field.error.hidden = true;
					}

					if ( 'erp_module_interest' === key ) {
						input.closest( '.abit-auth-module-option' ).classList.toggle( 'is-selected', input.checked );
					}

					saveDraft();
				} );
			} );
		} );

		if ( nextStep ) {
			nextStep.addEventListener( 'click', function () {
				if ( validateAccountStep() ) {
					saveDraft();
					setStep( 'company' );
					if ( fields.company_name.input ) {
						fields.company_name.input.focus();
					}
				}
			} );
		}

		if ( prevStep ) {
			prevStep.addEventListener( 'click', function () {
				saveDraft();
				setStep( 'account' );
				name.focus();
			} );
		}

		if ( nextCompanyStep ) {
			nextCompanyStep.addEventListener( 'click', function () {
				if ( validateCompanyStep() ) {
					saveDraft();
					setStep( 'modules' );
					if ( fields.erp_module_interest.input ) {
						fields.erp_module_interest.input.focus();
					}
				}
			} );
		}

		if ( prevCompanyStep ) {
			prevCompanyStep.addEventListener( 'click', function () {
				saveDraft();
				setStep( 'company' );
				if ( fields.company_name.input ) {
					fields.company_name.input.focus();
				}
			} );
		}

		restoreDraft();

		form.addEventListener( 'submit', function ( event ) {
			var isAccountValid = validateAccountStep();
			var isCompanyValid = isAccountValid ? validateCompanyStep() : false;
			var isModulesValid = isCompanyValid ? validateModulesStep() : false;

			if ( ! isAccountValid || ! isCompanyValid || ! isModulesValid ) {
				setStep( ! isAccountValid ? 'account' : ( ! isCompanyValid ? 'company' : 'modules' ) );
				event.preventDefault();
				return;
			}

			saveDraft();

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
