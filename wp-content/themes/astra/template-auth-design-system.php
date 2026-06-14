<?php
/**
 * Template Name: ABiT Auth Design System
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main abit-auth-page">
	<section class="abit-auth-shell" aria-labelledby="abit-auth-system-title">
		<header class="abit-auth-header">
			<span class="abit-auth-status-badge abit-auth-status-badge--review">UX-06</span>
			<h1 id="abit-auth-system-title"><?php esc_html_e( 'Auth UI design system', 'astra' ); ?></h1>
			<p><?php esc_html_e( 'Responsive component set for the abit.ai SaaS authentication and access request flow.', 'astra' ); ?></p>
		</header>

		<section class="abit-auth-panel" aria-labelledby="abit-auth-layout-title">
			<div class="abit-auth-panel__intro">
				<h2 id="abit-auth-layout-title"><?php esc_html_e( 'Responsive auth surface', 'astra' ); ?></h2>
				<p><?php esc_html_e( 'The same controls sit in a compact single-column form on mobile and a wider centered surface on tablet and desktop.', 'astra' ); ?></p>
			</div>

			<div class="abit-auth-card">
				<div class="abit-auth-stepper" aria-label="<?php echo esc_attr__( 'Signup progress', 'astra' ); ?>">
					<div class="abit-auth-stepper__item is-complete">
						<span class="abit-auth-stepper__marker">1</span>
						<span><?php esc_html_e( 'Account', 'astra' ); ?></span>
					</div>
					<div class="abit-auth-stepper__item is-active" aria-current="step">
						<span class="abit-auth-stepper__marker">2</span>
						<span><?php esc_html_e( 'Company', 'astra' ); ?></span>
					</div>
					<div class="abit-auth-stepper__item">
						<span class="abit-auth-stepper__marker">3</span>
						<span><?php esc_html_e( 'ERP needs', 'astra' ); ?></span>
					</div>
				</div>

				<div class="abit-auth-alert abit-auth-alert--error" role="alert">
					<strong><?php esc_html_e( 'Check two fields before continuing.', 'astra' ); ?></strong>
					<span><?php esc_html_e( 'Use inline field errors for exact recovery guidance.', 'astra' ); ?></span>
				</div>

				<form class="abit-auth-form" action="#" method="post">
					<div class="abit-auth-field">
						<label for="auth-system-name"><?php esc_html_e( 'Full name', 'astra' ); ?></label>
						<input id="auth-system-name" class="abit-auth-input" type="text" value="Jane Ahmed" />
						<p class="abit-auth-help"><?php esc_html_e( 'Use the requester name that admins should recognize.', 'astra' ); ?></p>
					</div>

					<div class="abit-auth-field is-error">
						<label for="auth-system-email"><?php esc_html_e( 'Business email', 'astra' ); ?></label>
						<input id="auth-system-email" class="abit-auth-input" type="email" value="jane@" aria-invalid="true" aria-describedby="auth-system-email-error" />
						<p id="auth-system-email-error" class="abit-auth-error"><?php esc_html_e( 'Enter a valid business email address.', 'astra' ); ?></p>
					</div>

					<div class="abit-auth-field">
						<label for="auth-system-password"><?php esc_html_e( 'Password', 'astra' ); ?></label>
						<div class="abit-auth-password">
							<input id="auth-system-password" class="abit-auth-input" type="password" value="sample-password" />
							<button type="button" class="abit-auth-icon-button" aria-label="<?php echo esc_attr__( 'Show password', 'astra' ); ?>"><?php esc_html_e( 'Show', 'astra' ); ?></button>
						</div>
						<div class="abit-auth-meter" aria-label="<?php echo esc_attr__( 'Password strength', 'astra' ); ?>">
							<span class="is-filled"></span>
							<span class="is-filled"></span>
							<span></span>
						</div>
						<p class="abit-auth-help"><?php esc_html_e( 'Minimum 12 characters.', 'astra' ); ?></p>
					</div>

					<div class="abit-auth-field">
						<label for="auth-system-role"><?php esc_html_e( 'Your role', 'astra' ); ?></label>
						<select id="auth-system-role" class="abit-auth-select">
							<option><?php esc_html_e( 'Operations manager', 'astra' ); ?></option>
							<option><?php esc_html_e( 'Finance leader', 'astra' ); ?></option>
							<option><?php esc_html_e( 'Founder or owner', 'astra' ); ?></option>
						</select>
					</div>

					<fieldset class="abit-auth-module-selector">
						<legend><?php esc_html_e( 'ERP module interest', 'astra' ); ?></legend>
						<p class="abit-auth-help"><?php esc_html_e( 'Select at least one ERP module interest.', 'astra' ); ?></p>
						<div class="abit-auth-module-grid">
							<label class="abit-auth-module-option is-selected">
								<input type="checkbox" checked />
								<span><?php esc_html_e( 'Accounting', 'astra' ); ?></span>
							</label>
							<label class="abit-auth-module-option">
								<input type="checkbox" />
								<span><?php esc_html_e( 'CRM', 'astra' ); ?></span>
							</label>
							<label class="abit-auth-module-option">
								<input type="checkbox" />
								<span><?php esc_html_e( 'Buying', 'astra' ); ?></span>
							</label>
							<label class="abit-auth-module-option is-disabled">
								<input type="checkbox" disabled />
								<span><?php esc_html_e( 'Stock', 'astra' ); ?></span>
							</label>
						</div>
					</fieldset>

					<label class="abit-auth-checkbox">
						<input type="checkbox" checked />
						<span><?php esc_html_e( 'I accept the current Terms of Service and Privacy Notice.', 'astra' ); ?></span>
					</label>

					<div class="abit-auth-actions">
						<button type="button" class="abit-auth-button abit-auth-button--primary"><?php esc_html_e( 'Continue', 'astra' ); ?></button>
						<button type="button" class="abit-auth-button abit-auth-button--secondary"><?php esc_html_e( 'Back', 'astra' ); ?></button>
					</div>
				</form>
			</div>
		</section>

		<section class="abit-auth-panel" aria-labelledby="abit-auth-variants-title">
			<div class="abit-auth-panel__intro">
				<h2 id="abit-auth-variants-title"><?php esc_html_e( 'States and variants', 'astra' ); ?></h2>
				<p><?php esc_html_e( 'Controls keep stable dimensions across hover, focus, disabled, loading, and error states.', 'astra' ); ?></p>
			</div>

			<div class="abit-auth-component-grid">
				<div class="abit-auth-example">
					<h3><?php esc_html_e( 'Alerts', 'astra' ); ?></h3>
					<div class="abit-auth-alert abit-auth-alert--info"><strong><?php esc_html_e( 'Verification required.', 'astra' ); ?></strong><span><?php esc_html_e( 'Confirm your business email before continuing.', 'astra' ); ?></span></div>
					<div class="abit-auth-alert abit-auth-alert--success"><strong><?php esc_html_e( 'Email verified.', 'astra' ); ?></strong><span><?php esc_html_e( 'Continue your access request.', 'astra' ); ?></span></div>
					<div class="abit-auth-alert abit-auth-alert--warning"><strong><?php esc_html_e( 'Please wait.', 'astra' ); ?></strong><span><?php esc_html_e( 'Request another email after the cooldown.', 'astra' ); ?></span></div>
				</div>

				<div class="abit-auth-example">
					<h3><?php esc_html_e( 'Buttons', 'astra' ); ?></h3>
					<button type="button" class="abit-auth-button abit-auth-button--primary"><?php esc_html_e( 'Submit for review', 'astra' ); ?></button>
					<button type="button" class="abit-auth-button abit-auth-button--primary is-loading" aria-busy="true"><?php esc_html_e( 'Submitting...', 'astra' ); ?></button>
					<button type="button" class="abit-auth-button abit-auth-button--secondary" disabled><?php esc_html_e( 'Disabled', 'astra' ); ?></button>
				</div>

				<div class="abit-auth-example">
					<h3><?php esc_html_e( 'Status badges', 'astra' ); ?></h3>
					<div class="abit-auth-badge-row">
						<span class="abit-auth-status-badge abit-auth-status-badge--pending"><?php esc_html_e( 'Pending email', 'astra' ); ?></span>
						<span class="abit-auth-status-badge abit-auth-status-badge--review"><?php esc_html_e( 'Admin review', 'astra' ); ?></span>
						<span class="abit-auth-status-badge abit-auth-status-badge--approved"><?php esc_html_e( 'Approved', 'astra' ); ?></span>
						<span class="abit-auth-status-badge abit-auth-status-badge--rejected"><?php esc_html_e( 'Rejected', 'astra' ); ?></span>
					</div>
				</div>

				<div class="abit-auth-example">
					<h3><?php esc_html_e( 'Disabled fields', 'astra' ); ?></h3>
					<div class="abit-auth-field">
						<label for="auth-system-disabled"><?php esc_html_e( 'Company size', 'astra' ); ?></label>
						<select id="auth-system-disabled" class="abit-auth-select" disabled>
							<option><?php esc_html_e( '11-50 employees', 'astra' ); ?></option>
						</select>
					</div>
					<label class="abit-auth-checkbox is-disabled">
						<input type="checkbox" disabled />
						<span><?php esc_html_e( 'Disabled checkbox state', 'astra' ); ?></span>
					</label>
				</div>
			</div>
		</section>
	</section>
</main>

<?php
get_footer();
