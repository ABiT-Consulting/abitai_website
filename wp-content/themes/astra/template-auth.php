<?php
/**
 * Shared abit.ai auth shell route template.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$routes    = function_exists( 'abitai_auth_get_routes' ) ? abitai_auth_get_routes() : array();
$route_key = isset( $GLOBALS['abitai_auth_route_key'] ) ? sanitize_key( $GLOBALS['abitai_auth_route_key'] ) : 'sign-in';

if ( ! isset( $routes[ $route_key ] ) ) {
	$route_key = 'sign-in';
}

$route        = $routes[ $route_key ];
$support_href = 'mailto:support@abit.ai';

if ( 'verify' === $route_key ) {
	$verification_state = abitai_auth_get_verification_state();
	$verification_copy  = abitai_auth_get_verification_state_copy( $verification_state );

	$route['title']       = $verification_copy['title'];
	$route['eyebrow']     = $verification_copy['eyebrow'];
	$route['description'] = $verification_copy['description'];
} elseif ( 'reset' === $route_key ) {
	$reset_state = abitai_auth_get_reset_state();
	$reset_copy  = abitai_auth_get_reset_state_copy( $reset_state );

	$route['title']       = $reset_copy['title'];
	$route['eyebrow']     = $reset_copy['eyebrow'];
	$route['description'] = $reset_copy['description'];
}

status_header( 200 );
nocache_headers();

get_header();
?>

<main id="primary" class="site-main abit-auth-page abit-auth-route-page">
	<section class="abit-auth-app-shell" aria-labelledby="abit-auth-route-title">
		<aside class="abit-auth-brand-panel" aria-label="<?php echo esc_attr__( 'abit.ai access request support', 'astra' ); ?>">
			<a class="abit-auth-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="abit-auth-brand__mark" aria-hidden="true">ai</span>
				<span class="abit-auth-brand__name"><?php esc_html_e( 'abit.ai', 'astra' ); ?></span>
			</a>
			<div class="abit-auth-brand-panel__content">
				<p class="abit-auth-kicker"><?php esc_html_e( 'ERPNext access portal', 'astra' ); ?></p>
				<h1><?php esc_html_e( 'Business software access, reviewed by the abit.ai team.', 'astra' ); ?></h1>
				<p><?php esc_html_e( 'Request access, verify your business email, and return to the right next step from one secure auth surface.', 'astra' ); ?></p>
			</div>
			<a class="abit-auth-support-link" href="<?php echo esc_url( $support_href ); ?>"><?php esc_html_e( 'Need help? Contact support', 'astra' ); ?></a>
		</aside>

		<section class="abit-auth-route-panel">
			<div class="abit-auth-route-card" data-auth-route="<?php echo esc_attr( $route_key ); ?>">
				<header class="abit-auth-route-header">
					<p class="abit-auth-kicker"><?php echo esc_html( $route['eyebrow'] ); ?></p>
					<h2 id="abit-auth-route-title"><?php echo esc_html( $route['title'] ); ?></h2>
					<p><?php echo esc_html( $route['description'] ); ?></p>
				</header>

				<?php
				switch ( $route_key ) :
					case 'signup':
						abitai_auth_render_signup_slot();
						break;
					case 'verify':
						abitai_auth_render_verify_slot();
						break;
					case 'reset':
						abitai_auth_render_reset_slot();
						break;
					case 'onboarding':
					case 'review':
					case 'more-info':
					case 'rejected':
					case 'dashboard':
					case 'app':
						if ( 'dashboard' === $route_key ) {
							abitai_auth_render_dashboard_gate_slot();
						} else {
							abitai_auth_render_status_slot( $route_key );
						}
						break;
					case 'sign-in':
					default:
						abitai_auth_render_sign_in_slot();
						break;
				endswitch;
				?>
			</div>
		</section>
	</section>
</main>

<?php
get_footer();

function abitai_auth_render_sign_in_slot() {
	$signin_error = isset( $_GET['signin_error'] ) ? sanitize_key( wp_unslash( $_GET['signin_error'] ) ) : '';
	$email_value   = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
	?>
	<?php if ( 'invalid' === $signin_error ) : ?>
		<div id="abit-auth-signin-error" class="abit-auth-alert abit-auth-alert--error" role="alert" tabindex="-1" data-auth-autofocus>
			<strong><?php esc_html_e( 'We could not sign you in with those details.', 'astra' ); ?></strong>
			<span><?php esc_html_e( 'Check your email and password, then try again.', 'astra' ); ?></span>
		</div>
	<?php endif; ?>
	<div class="abit-auth-alert abit-auth-alert--success abit-auth-signin-success" role="status" hidden>
		<strong><?php esc_html_e( 'Signed in. Redirecting...', 'astra' ); ?></strong>
	</div>
	<form class="abit-auth-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-auth-signin-form novalidate>
		<input type="hidden" name="action" value="abitai_mock_sign_in" />
		<?php wp_nonce_field( 'abitai_mock_sign_in', 'abitai_signin_nonce' ); ?>

		<div class="abit-auth-field" data-auth-field="email">
			<label for="abit-auth-email"><?php esc_html_e( 'Email address', 'astra' ); ?></label>
			<input id="abit-auth-email" class="abit-auth-input" type="email" name="email" autocomplete="email" inputmode="email" value="<?php echo esc_attr( $email_value ); ?>" aria-describedby="abit-auth-email-error" required />
			<p id="abit-auth-email-error" class="abit-auth-error" hidden><?php esc_html_e( 'Enter a valid email address.', 'astra' ); ?></p>
		</div>
		<div class="abit-auth-field" data-auth-field="password">
			<label for="abit-auth-password"><?php esc_html_e( 'Password', 'astra' ); ?></label>
			<input id="abit-auth-password" class="abit-auth-input" type="password" name="password" autocomplete="current-password" aria-describedby="abit-auth-password-error" required />
			<p id="abit-auth-password-error" class="abit-auth-error" hidden><?php esc_html_e( 'Enter your password.', 'astra' ); ?></p>
		</div>
		<div class="abit-auth-inline-row">
			<label class="abit-auth-checkbox">
				<input type="checkbox" name="rememberme" value="forever" />
				<span><?php esc_html_e( 'Remember this device', 'astra' ); ?></span>
			</label>
			<a href="<?php echo esc_url( home_url( '/auth/reset' ) ); ?>" data-auth-lockable><?php esc_html_e( 'Forgot password?', 'astra' ); ?></a>
		</div>
		<div class="abit-auth-actions">
			<button type="submit" class="abit-auth-button abit-auth-button--primary" data-auth-submit data-loading-label="<?php echo esc_attr__( 'Signing in...', 'astra' ); ?>"><?php esc_html_e( 'Sign in', 'astra' ); ?></button>
		</div>
	</form>
	<p class="abit-auth-route-footer">
		<?php esc_html_e( 'New to abit.ai?', 'astra' ); ?>
		<a href="<?php echo esc_url( home_url( '/auth/signup' ) ); ?>" data-auth-lockable><?php esc_html_e( 'Start access request', 'astra' ); ?></a>
	</p>
	<?php
}

function abitai_auth_render_status_slot( $route_key ) {
	$status_copy = array(
		'onboarding' => array(
			'variant' => 'info',
			'strong'  => __( 'Onboarding required.', 'astra' ),
			'body'    => __( 'Your email is verified. Complete onboarding so the review team has the required business context.', 'astra' ),
			'action'  => __( 'Continue onboarding', 'astra' ),
		),
		'review'     => array(
			'variant' => 'warning',
			'strong'  => __( 'Request submitted for review.', 'astra' ),
			'body'    => __( 'You can sign in, but product access remains blocked until admin approval.', 'astra' ),
			'action'  => __( 'Back to sign in', 'astra' ),
		),
		'more-info'  => array(
			'variant' => 'warning',
			'strong'  => __( 'More information is needed.', 'astra' ),
			'body'    => __( 'Update the requested details before the review can continue.', 'astra' ),
			'action'  => __( 'Update request', 'astra' ),
		),
		'rejected'   => array(
			'variant' => 'error',
			'strong'  => __( 'Product access is unavailable.', 'astra' ),
			'body'    => __( 'This access request is not approved for MVP access.', 'astra' ),
			'action'  => __( 'Contact support', 'astra' ),
		),
		'app'        => array(
			'variant' => 'success',
			'strong'  => __( 'Signed in. Redirecting...', 'astra' ),
			'body'    => __( 'Mocked login succeeded for an approved account.', 'astra' ),
			'action'  => __( 'Go to home', 'astra' ),
		),
	);

	$copy = isset( $status_copy[ $route_key ] ) ? $status_copy[ $route_key ] : $status_copy['app'];
	?>
	<div class="abit-auth-alert abit-auth-alert--<?php echo esc_attr( $copy['variant'] ); ?>" role="status">
		<strong><?php echo esc_html( $copy['strong'] ); ?></strong>
		<span><?php echo esc_html( $copy['body'] ); ?></span>
	</div>
	<div class="abit-auth-actions">
		<a class="abit-auth-button abit-auth-button--primary" href="<?php echo esc_url( 'rejected' === $route_key ? 'mailto:support@abit.ai' : home_url( '/' ) ); ?>"><?php echo esc_html( $copy['action'] ); ?></a>
		<a class="abit-auth-button abit-auth-button--secondary" href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>"><?php esc_html_e( 'Back to sign in', 'astra' ); ?></a>
	</div>
	<?php
}

function abitai_auth_render_dashboard_gate_slot() {
	$request = function_exists( 'abitai_auth_get_dashboard_request' ) ? abitai_auth_get_dashboard_request() : array(
		'status'       => 'onboarding_required',
		'status_label' => __( 'Company profile incomplete', 'astra' ),
		'email'        => '',
		'company_name' => '',
	);
	$gate    = function_exists( 'abitai_auth_get_dashboard_gate' ) ? abitai_auth_get_dashboard_gate( $request ) : array(
		'profile_state'        => __( 'Incomplete', 'astra' ),
		'profile_variant'      => 'pending',
		'profile_description'  => __( 'Complete the required company profile fields before admin review can begin.', 'astra' ),
		'provisioning_state'   => __( 'Blocked', 'astra' ),
		'provisioning_variant' => 'rejected',
		'next_action'          => __( 'Complete company profile', 'astra' ),
		'next_href'            => home_url( '/auth/onboarding' ),
		'next_variant'         => 'primary',
		'alert_variant'        => 'info',
		'alert_summary'        => __( 'Company profile completion required.', 'astra' ),
		'alert_body'           => __( 'Your email is verified. Complete your company profile so the abit.ai team can review the access request.', 'astra' ),
	);
	$email        = isset( $request['email'] ) && '' !== $request['email'] ? $request['email'] : __( 'Signed-in account', 'astra' );
	$company_name = isset( $request['company_name'] ) && '' !== $request['company_name'] ? $request['company_name'] : __( 'Not provided yet', 'astra' );
	?>
	<div class="abit-auth-alert abit-auth-alert--<?php echo esc_attr( $gate['alert_variant'] ); ?>" role="status" tabindex="-1" data-auth-autofocus>
		<strong><?php echo esc_html( $gate['alert_summary'] ); ?></strong>
		<span><?php echo esc_html( $gate['alert_body'] ); ?></span>
	</div>

	<div class="abit-auth-dashboard-summary" aria-label="<?php echo esc_attr__( 'Access request summary', 'astra' ); ?>">
		<div class="abit-auth-status-box">
			<span><?php esc_html_e( 'Business email', 'astra' ); ?></span>
			<strong><?php echo esc_html( $email ); ?></strong>
		</div>
		<div class="abit-auth-status-box">
			<span><?php esc_html_e( 'Company', 'astra' ); ?></span>
			<strong><?php echo esc_html( $company_name ); ?></strong>
		</div>
		<div class="abit-auth-status-box">
			<span><?php esc_html_e( 'Request status', 'astra' ); ?></span>
			<strong><?php echo esc_html( $request['status_label'] ); ?></strong>
		</div>
	</div>

	<div class="abit-auth-dashboard-gate">
		<section class="abit-auth-gate-panel">
			<div class="abit-auth-gate-panel__heading">
				<span class="abit-auth-status-badge abit-auth-status-badge--<?php echo esc_attr( $gate['profile_variant'] ); ?>"><?php echo esc_html( $gate['profile_state'] ); ?></span>
				<h3><?php esc_html_e( 'Company profile status', 'astra' ); ?></h3>
			</div>
			<p><?php echo esc_html( $gate['profile_description'] ); ?></p>
		</section>

		<section class="abit-auth-gate-panel">
			<div class="abit-auth-gate-panel__heading">
				<span class="abit-auth-status-badge abit-auth-status-badge--<?php echo esc_attr( $gate['provisioning_variant'] ); ?>"><?php echo esc_html( $gate['provisioning_state'] ); ?></span>
				<h3><?php esc_html_e( 'Provisioning status', 'astra' ); ?></h3>
			</div>
			<p><?php esc_html_e( 'Workspace provisioning follows verification, profile completion, and admin approval.', 'astra' ); ?></p>
		</section>
	</div>

	<div class="abit-auth-actions">
		<a class="abit-auth-button abit-auth-button--<?php echo esc_attr( 'secondary' === $gate['next_variant'] ? 'secondary' : 'primary' ); ?>" href="<?php echo esc_url( $gate['next_href'] ); ?>"><?php echo esc_html( $gate['next_action'] ); ?></a>
		<a class="abit-auth-button abit-auth-button--secondary" href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>"><?php esc_html_e( 'Back to sign in', 'astra' ); ?></a>
	</div>
	<?php
}

function abitai_auth_render_signup_slot() {
	$signup_error = isset( $_GET['signup_error'] ) ? sanitize_key( wp_unslash( $_GET['signup_error'] ) ) : '';
	$email_value  = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
	$name_value   = isset( $_GET['full_name'] ) ? sanitize_text_field( wp_unslash( $_GET['full_name'] ) ) : '';
	$company_values = array(
		'company_name'         => isset( $_GET['company_name'] ) ? sanitize_text_field( wp_unslash( $_GET['company_name'] ) ) : '',
		'industry'             => isset( $_GET['industry'] ) ? sanitize_key( wp_unslash( $_GET['industry'] ) ) : '',
		'company_size'         => isset( $_GET['company_size'] ) ? sanitize_key( wp_unslash( $_GET['company_size'] ) ) : '',
		'business_description' => isset( $_GET['business_description'] ) ? sanitize_textarea_field( wp_unslash( $_GET['business_description'] ) ) : '',
		'job_title'            => isset( $_GET['job_title'] ) ? sanitize_text_field( wp_unslash( $_GET['job_title'] ) ) : '',
		'country_region'       => isset( $_GET['country_region'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['country_region'] ) ) ) : '',
		'phone'                => isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : '',
	);
	$selected_modules = array();

	if ( isset( $_GET['erp_module_interest'] ) && is_array( $_GET['erp_module_interest'] ) ) {
		$selected_modules = array_values( array_unique( array_map( 'sanitize_key', wp_unslash( $_GET['erp_module_interest'] ) ) ) );
	}

	$error_messages = array(
		'invalid_nonce'     => __( 'Your session expired. Please try submitting again.', 'astra' ),
		'missing_fields'    => __( 'Complete this field to continue.', 'astra' ),
		'invalid_email'     => __( 'Enter a valid business email address.', 'astra' ),
		'weak_password'     => __( 'Use at least 12 characters and avoid common passwords.', 'astra' ),
		'password_mismatch' => __( 'Passwords do not match.', 'astra' ),
		'missing_consent'   => __( 'Accept the current terms and privacy notices to submit for review.', 'astra' ),
		'user_exists'       => __( 'A request may already exist for this email. Sign in or check your inbox for the next step.', 'astra' ),
		'create_failed'     => __( 'We could not start your access request right now. Please try again.', 'astra' ),
		'invalid_company'   => __( 'Enter a company name, not a website or markup.', 'astra' ),
		'invalid_select'    => __( 'Choose an option to continue.', 'astra' ),
		'invalid_business_description' => __( 'Describe the workflow in at least 20 characters.', 'astra' ),
		'invalid_job_title' => __( 'Complete this field to continue.', 'astra' ),
		'missing_module_interest' => __( 'Select at least one ERP module interest.', 'astra' ),
	);

	$company_size_options = function_exists( 'abitai_get_company_size_options' ) ? abitai_get_company_size_options() : array();
	$industry_options     = function_exists( 'abitai_get_industry_options' ) ? abitai_get_industry_options() : array();
	$country_options      = function_exists( 'abitai_get_country_region_options' ) ? abitai_get_country_region_options() : array();
	$module_options       = function_exists( 'abitai_get_erp_module_interest_options' ) ? abitai_get_erp_module_interest_options() : array();
	?>
	<?php if ( isset( $error_messages[ $signup_error ] ) ) : ?>
		<div id="abit-auth-signup-error" class="abit-auth-alert abit-auth-alert--error" role="alert" tabindex="-1" data-auth-autofocus>
			<strong><?php esc_html_e( 'We could not start your request.', 'astra' ); ?></strong>
			<span><?php echo esc_html( $error_messages[ $signup_error ] ); ?></span>
		</div>
	<?php endif; ?>
	<div class="abit-auth-alert abit-auth-alert--success abit-auth-signup-success" role="status" hidden>
		<strong><?php esc_html_e( 'Creating request...', 'astra' ); ?></strong>
	</div>
	<ol class="abit-auth-stepper" aria-label="<?php echo esc_attr__( 'Signup progress', 'astra' ); ?>">
		<li class="abit-auth-stepper__item is-active" aria-current="step" data-auth-stepper-item="account"><span class="abit-auth-stepper__marker" aria-hidden="true">1</span><span><?php esc_html_e( 'Account', 'astra' ); ?></span></li>
		<li class="abit-auth-stepper__item" data-auth-stepper-item="company"><span class="abit-auth-stepper__marker" aria-hidden="true">2</span><span><?php esc_html_e( 'Company', 'astra' ); ?></span></li>
		<li class="abit-auth-stepper__item" data-auth-stepper-item="modules"><span class="abit-auth-stepper__marker" aria-hidden="true">3</span><span><?php esc_html_e( 'ERP needs', 'astra' ); ?></span></li>
	</ol>
	<p class="abit-auth-sr-only" aria-live="polite" data-auth-step-status><?php esc_html_e( 'Step 1 of 3: Account', 'astra' ); ?></p>
	<form class="abit-auth-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-auth-signup-form novalidate>
		<input type="hidden" name="action" value="abitai_user_signup" />
		<input type="hidden" name="abitai_signup_flow_version" value="company_step" />
		<input type="hidden" name="abitai_redirect" value="<?php echo esc_url( home_url( '/auth/signup' ) ); ?>" />
		<input type="hidden" name="abitai_success_redirect" value="<?php echo esc_url( home_url( '/auth/verify?state=sent' ) ); ?>" />
		<?php wp_nonce_field( 'abitai_user_signup', 'abitai_signup_nonce' ); ?>
		<div data-auth-signup-step="account">
			<div class="abit-auth-field" data-auth-field="full_name">
				<label for="abit-auth-signup-name"><?php esc_html_e( 'Full name', 'astra' ); ?></label>
				<input id="abit-auth-signup-name" class="abit-auth-input" type="text" name="full_name" autocomplete="name" placeholder="<?php echo esc_attr__( 'Jane Ahmed', 'astra' ); ?>" value="<?php echo esc_attr( $name_value ); ?>" aria-describedby="abit-auth-signup-name-error" required />
				<p id="abit-auth-signup-name-error" class="abit-auth-error" hidden><?php esc_html_e( 'Complete this field to continue.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="email">
				<label for="abit-auth-signup-email"><?php esc_html_e( 'Business email', 'astra' ); ?></label>
				<input id="abit-auth-signup-email" class="abit-auth-input" type="email" name="email" autocomplete="email" inputmode="email" placeholder="<?php echo esc_attr__( 'jane@company.com', 'astra' ); ?>" value="<?php echo esc_attr( $email_value ); ?>" aria-describedby="abit-auth-signup-email-error" required />
				<p id="abit-auth-signup-email-error" class="abit-auth-error" hidden><?php esc_html_e( 'Enter a valid business email address.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="password">
				<label for="abit-auth-signup-password"><?php esc_html_e( 'Password', 'astra' ); ?></label>
				<input id="abit-auth-signup-password" class="abit-auth-input" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="128" placeholder="<?php echo esc_attr__( 'Create a password', 'astra' ); ?>" aria-describedby="abit-auth-signup-password-help abit-auth-signup-password-error" required />
				<p id="abit-auth-signup-password-help" class="abit-auth-help"><?php esc_html_e( 'Minimum 12 characters.', 'astra' ); ?></p>
				<p id="abit-auth-signup-password-error" class="abit-auth-error" hidden><?php esc_html_e( 'Use at least 12 characters and avoid common passwords.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="confirm_password">
				<label for="abit-auth-signup-confirm-password"><?php esc_html_e( 'Confirm new password', 'astra' ); ?></label>
				<input id="abit-auth-signup-confirm-password" class="abit-auth-input" type="password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="128" aria-describedby="abit-auth-signup-confirm-password-error" required />
				<p id="abit-auth-signup-confirm-password-error" class="abit-auth-error" hidden><?php esc_html_e( 'Passwords do not match.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="consent">
				<label class="abit-auth-checkbox" for="abit-auth-signup-consent">
					<input id="abit-auth-signup-consent" type="checkbox" name="terms_privacy_acceptance" value="1" aria-describedby="abit-auth-signup-consent-error" required />
					<span>
						<?php
						printf(
							/* translators: 1: Terms URL, 2: Privacy URL. */
							wp_kses_post( __( 'I accept the current <a href="%1$s" target="_blank" rel="noopener">terms</a> and <a href="%2$s" target="_blank" rel="noopener">privacy notices</a>.', 'astra' ) ),
							esc_url( home_url( '/terms' ) ),
							esc_url( home_url( '/privacy-policy' ) )
						);
						?>
					</span>
				</label>
				<p id="abit-auth-signup-consent-error" class="abit-auth-error" hidden><?php esc_html_e( 'Accept the current terms and privacy notices to submit for review.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-actions">
				<button type="button" class="abit-auth-button abit-auth-button--primary" data-auth-next-step><?php esc_html_e( 'Continue', 'astra' ); ?></button>
			</div>
		</div>
		<div data-auth-signup-step="company" hidden>
			<div class="abit-auth-route-header abit-auth-step-header">
				<h3><?php esc_html_e( 'Company profile', 'astra' ); ?></h3>
				<p><?php esc_html_e( 'This helps us route your request for review.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="company_name">
				<label for="abit-auth-company-name"><?php esc_html_e( 'Company name', 'astra' ); ?></label>
				<input id="abit-auth-company-name" class="abit-auth-input" type="text" name="company_name" autocomplete="organization" minlength="2" maxlength="160" placeholder="<?php echo esc_attr__( 'ABiT Trading LLC', 'astra' ); ?>" value="<?php echo esc_attr( $company_values['company_name'] ); ?>" aria-describedby="abit-auth-company-name-error" required />
				<p id="abit-auth-company-name-error" class="abit-auth-error" hidden><?php esc_html_e( 'Enter a company name, not a website or markup.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="country_region">
				<label for="abit-auth-country-region"><?php esc_html_e( 'Country or region', 'astra' ); ?></label>
				<select id="abit-auth-country-region" class="abit-auth-select" name="country_region" autocomplete="country" aria-describedby="abit-auth-country-region-error" required>
					<option value=""><?php esc_html_e( 'Select country or region', 'astra' ); ?></option>
					<?php foreach ( $country_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $company_values['country_region'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p id="abit-auth-country-region-error" class="abit-auth-error" hidden><?php esc_html_e( 'Choose an option to continue.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="job_title">
				<label for="abit-auth-job-title"><?php esc_html_e( 'Job title', 'astra' ); ?></label>
				<input id="abit-auth-job-title" class="abit-auth-input" type="text" name="job_title" autocomplete="organization-title" minlength="2" maxlength="120" placeholder="<?php echo esc_attr__( 'Operations manager', 'astra' ); ?>" value="<?php echo esc_attr( $company_values['job_title'] ); ?>" aria-describedby="abit-auth-job-title-error" required />
				<p id="abit-auth-job-title-error" class="abit-auth-error" hidden><?php esc_html_e( 'Complete this field to continue.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="company_size">
				<label for="abit-auth-company-size"><?php esc_html_e( 'Company size', 'astra' ); ?></label>
				<select id="abit-auth-company-size" class="abit-auth-select" name="company_size" aria-describedby="abit-auth-company-size-error" required>
					<option value=""><?php esc_html_e( 'Select company size', 'astra' ); ?></option>
					<?php foreach ( $company_size_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $company_values['company_size'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p id="abit-auth-company-size-error" class="abit-auth-error" hidden><?php esc_html_e( 'Choose an option to continue.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="industry">
				<label for="abit-auth-industry"><?php esc_html_e( 'Industry', 'astra' ); ?></label>
				<select id="abit-auth-industry" class="abit-auth-select" name="industry" aria-describedby="abit-auth-industry-error" required>
					<option value=""><?php esc_html_e( 'Select industry', 'astra' ); ?></option>
					<?php foreach ( $industry_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $company_values['industry'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p id="abit-auth-industry-error" class="abit-auth-error" hidden><?php esc_html_e( 'Choose an option to continue.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="business_description">
				<label for="abit-auth-business-description"><?php esc_html_e( 'Business description', 'astra' ); ?></label>
				<textarea id="abit-auth-business-description" class="abit-auth-input" name="business_description" rows="4" minlength="20" maxlength="1000" placeholder="<?php echo esc_attr__( 'Describe the business outcome you need.', 'astra' ); ?>" aria-describedby="abit-auth-business-description-error" required><?php echo esc_textarea( $company_values['business_description'] ); ?></textarea>
				<p id="abit-auth-business-description-error" class="abit-auth-error" hidden><?php esc_html_e( 'Describe the workflow in at least 20 characters.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-field" data-auth-field="phone">
				<label for="abit-auth-phone"><?php esc_html_e( 'Phone', 'astra' ); ?> <span class="abit-auth-optional"><?php esc_html_e( 'Optional', 'astra' ); ?></span></label>
				<input id="abit-auth-phone" class="abit-auth-input" type="tel" name="phone" autocomplete="tel" maxlength="40" placeholder="<?php echo esc_attr__( '+971 50 123 4567', 'astra' ); ?>" value="<?php echo esc_attr( $company_values['phone'] ); ?>" aria-describedby="abit-auth-phone-error" />
				<p id="abit-auth-phone-error" class="abit-auth-error" hidden><?php esc_html_e( 'Enter a valid phone number or leave it blank.', 'astra' ); ?></p>
			</div>
			<div class="abit-auth-actions">
				<button type="button" class="abit-auth-button abit-auth-button--primary" data-auth-next-company-step><?php esc_html_e( 'Continue', 'astra' ); ?></button>
				<button type="button" class="abit-auth-button abit-auth-button--secondary" data-auth-prev-step><?php esc_html_e( 'Back', 'astra' ); ?></button>
			</div>
		</div>
		<div data-auth-signup-step="modules" hidden>
			<div class="abit-auth-route-header abit-auth-step-header">
				<h3><?php esc_html_e( 'ERP module interest', 'astra' ); ?></h3>
				<p><?php esc_html_e( 'Select the ERPNext areas you want to evaluate first.', 'astra' ); ?></p>
			</div>
			<fieldset class="abit-auth-module-selector" data-auth-field="erp_module_interest" aria-describedby="abit-auth-module-interest-help abit-auth-module-interest-error">
				<legend><?php esc_html_e( 'ERP module interest', 'astra' ); ?></legend>
				<p id="abit-auth-module-interest-help" class="abit-auth-help"><?php esc_html_e( 'Choose one or more areas.', 'astra' ); ?></p>
				<p id="abit-auth-module-interest-error" class="abit-auth-error" hidden><?php esc_html_e( 'Select at least one ERP module interest.', 'astra' ); ?></p>
				<div class="abit-auth-module-grid">
					<?php foreach ( $module_options as $value => $label ) : ?>
						<label class="abit-auth-module-option<?php echo in_array( $value, $selected_modules, true ) ? ' is-selected' : ''; ?>">
							<input type="checkbox" name="erp_module_interest[]" value="<?php echo esc_attr( $value ); ?>" aria-describedby="abit-auth-module-interest-help abit-auth-module-interest-error" <?php checked( in_array( $value, $selected_modules, true ) ); ?> />
							<span><?php echo esc_html( $label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<div class="abit-auth-actions">
				<button type="submit" class="abit-auth-button abit-auth-button--primary" data-auth-submit data-loading-label="<?php echo esc_attr__( 'Submitting request...', 'astra' ); ?>"><?php esc_html_e( 'Submit request', 'astra' ); ?></button>
				<button type="button" class="abit-auth-button abit-auth-button--secondary" data-auth-prev-company-step><?php esc_html_e( 'Back', 'astra' ); ?></button>
			</div>
		</div>
	</form>
	<p class="abit-auth-route-footer">
		<?php esc_html_e( 'Already requested access?', 'astra' ); ?>
		<a href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>" data-auth-lockable><?php esc_html_e( 'Sign in', 'astra' ); ?></a>
	</p>
	<?php
}

function abitai_auth_render_verify_slot() {
	$state         = abitai_auth_get_verification_state();
	$copy          = abitai_auth_get_verification_state_copy( $state );
	$email_value   = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
	$email_label   = '' !== $email_value ? $email_value : __( 'your business email', 'astra' );
	$resend_result = isset( $_GET['resend'] ) ? sanitize_key( wp_unslash( $_GET['resend'] ) ) : '';
	$mock_response = isset( $_GET['mock_response'] ) ? sanitize_key( wp_unslash( $_GET['mock_response'] ) ) : '';
	?>
	<div id="abit-auth-verification-status" class="abit-auth-alert abit-auth-alert--<?php echo esc_attr( $copy['variant'] ); ?>" role="<?php echo esc_attr( 'failed' === $state ? 'alert' : 'status' ); ?>" tabindex="-1" data-auth-autofocus>
		<strong><?php echo esc_html( $copy['summary'] ); ?></strong>
		<span><?php echo esc_html( $copy['body'] ); ?></span>
	</div>

	<?php if ( 'failed' !== $state || '' !== $email_value ) : ?>
		<div class="abit-auth-status-box">
			<span><?php echo esc_html( $copy['email_label'] ); ?></span>
			<strong><?php echo esc_html( $email_label ); ?></strong>
		</div>
	<?php endif; ?>

	<?php if ( 'accepted' === $resend_result ) : ?>
		<div class="abit-auth-alert abit-auth-alert--success" role="status" tabindex="-1" data-auth-autofocus>
			<strong><?php esc_html_e( 'Verification email sent.', 'astra' ); ?></strong>
			<span><?php esc_html_e( 'Check your inbox and spam folder.', 'astra' ); ?></span>
		</div>
	<?php endif; ?>

	<?php if ( $copy['resend'] ) : ?>
		<form class="abit-auth-form abit-auth-resend-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-auth-resend-form>
			<input type="hidden" name="action" value="abitai_mock_resend_verification" />
			<input type="hidden" name="state" value="<?php echo esc_attr( $state ); ?>" />
			<input type="hidden" name="mock_response" value="<?php echo esc_attr( $mock_response ); ?>" />
			<?php wp_nonce_field( 'abitai_mock_resend_verification', 'abitai_resend_nonce' ); ?>
			<?php if ( '' === $email_value && 'failed' === $state ) : ?>
				<div class="abit-auth-field" data-auth-field="resend_email">
					<label for="abit-auth-resend-email"><?php esc_html_e( 'Business email', 'astra' ); ?></label>
					<input id="abit-auth-resend-email" class="abit-auth-input" type="email" name="email" autocomplete="email" inputmode="email" aria-describedby="abit-auth-resend-email-help abit-auth-resend-email-error" required />
					<p id="abit-auth-resend-email-help" class="abit-auth-help"><?php esc_html_e( 'If an eligible request exists, we will send a new verification link.', 'astra' ); ?></p>
					<p id="abit-auth-resend-email-error" class="abit-auth-error" hidden><?php esc_html_e( 'Enter a valid business email address.', 'astra' ); ?></p>
				</div>
			<?php else : ?>
				<input type="hidden" name="email" value="<?php echo esc_attr( $email_value ); ?>" />
			<?php endif; ?>
			<div class="abit-auth-actions">
				<button type="submit" class="abit-auth-button abit-auth-button--primary" data-auth-submit data-loading-label="<?php echo esc_attr__( 'Sending...', 'astra' ); ?>"><?php echo esc_html( $copy['primary_action'] ); ?></button>
			</div>
		</form>
	<?php else : ?>
		<div class="abit-auth-actions">
			<a class="abit-auth-button abit-auth-button--primary" href="<?php echo esc_url( $copy['primary_href'] ); ?>"><?php echo esc_html( $copy['primary_action'] ); ?></a>
		</div>
	<?php endif; ?>

	<div class="abit-auth-actions">
		<?php foreach ( $copy['secondary_actions'] as $action ) : ?>
			<?php
			$action_href = $action['href'];
			if ( 'sent' === $state && false !== strpos( $action_href, 'state=required' ) ) {
				$link_args = array();
				if ( '' !== $email_value ) {
					$link_args['email'] = $email_value;
				}
				if ( '' !== $mock_response ) {
					$link_args['mock_response'] = $mock_response;
				}
				$action_href = add_query_arg( $link_args, $action_href );
			}
			?>
			<a class="abit-auth-button abit-auth-button--secondary" href="<?php echo esc_url( $action_href ); ?>" data-auth-lockable><?php echo esc_html( $action['label'] ); ?></a>
		<?php endforeach; ?>
	</div>
	<p class="abit-auth-route-footer">
		<?php echo wp_kses_post( $copy['footer'] ); ?>
	</p>
	<?php
}

function abitai_auth_get_verification_state() {
	$state = isset( $_GET['state'] ) ? sanitize_key( wp_unslash( $_GET['state'] ) ) : 'required';
	$state_aliases = array(
		'email_sent'       => 'sent',
		'verified'         => 'success',
		'expired_link'     => 'expired',
		'invalid'          => 'failed',
		'rate_limited'     => 'cooldown',
		'already-verified' => 'already_verified',
		'already'          => 'already_verified',
	);

	if ( isset( $state_aliases[ $state ] ) ) {
		$state = $state_aliases[ $state ];
	}

	$allowed_states = array( 'required', 'sent', 'success', 'expired', 'failed', 'cooldown', 'already_verified' );

	return in_array( $state, $allowed_states, true ) ? $state : 'required';
}

function abitai_auth_get_verification_state_copy( $state ) {
	$signin_link  = sprintf( '<a href="%s">%s</a>', esc_url( home_url( '/auth/sign-in' ) ), esc_html__( 'Back to sign in', 'astra' ) );
	$support_link = sprintf( '<a href="%s">%s</a>', esc_url( 'mailto:support@abit.ai' ), esc_html__( 'Contact support', 'astra' ) );

	$states = array(
		'required' => array(
			'eyebrow'           => __( 'Email verification', 'astra' ),
			'title'             => __( 'Verify your email to continue', 'astra' ),
			'description'       => __( 'Verification is required before onboarding or product access can continue.', 'astra' ),
			'variant'           => 'info',
			'summary'           => __( 'Verification required.', 'astra' ),
			'body'              => __( 'We need to confirm your business email before you can continue the access request.', 'astra' ),
			'email_label'       => __( 'Verification email sent to:', 'astra' ),
			'primary_action'    => __( 'Resend verification email', 'astra' ),
			'primary_href'      => '',
			'resend'            => true,
			'secondary_actions' => array(
				array( 'label' => __( 'Change email', 'astra' ), 'href' => home_url( '/auth/signup' ) ),
				array( 'label' => __( 'Sign out', 'astra' ), 'href' => home_url( '/auth/sign-in' ) ),
			),
			'footer'            => $support_link,
		),
		'sent' => array(
			'eyebrow'           => __( 'Email sent', 'astra' ),
			'title'             => __( 'Check your email', 'astra' ),
			'description'       => __( 'Open the verification link in your email to continue your access request.', 'astra' ),
			'variant'           => 'info',
			'summary'           => __( 'Verification email sent.', 'astra' ),
			'body'              => __( 'Open the link in that email to verify your address. Verification is required before your access request can continue.', 'astra' ),
			'email_label'       => __( 'We sent a verification link to:', 'astra' ),
			'primary_action'    => __( 'Open email app', 'astra' ),
			'primary_href'      => 'mailto:',
			'resend'            => false,
			'secondary_actions' => array(
				array( 'label' => __( 'Resend verification email', 'astra' ), 'href' => add_query_arg( array( 'state' => 'required' ), home_url( '/auth/verify' ) ) ),
				array( 'label' => __( 'Change email', 'astra' ), 'href' => home_url( '/auth/signup' ) ),
				array( 'label' => __( 'Sign in', 'astra' ), 'href' => home_url( '/auth/sign-in' ) ),
			),
			'footer'            => $support_link,
		),
		'success' => array(
			'eyebrow'           => __( 'Verified', 'astra' ),
			'title'             => __( 'Email verified', 'astra' ),
			'description'       => __( 'Your business email has been confirmed.', 'astra' ),
			'variant'           => 'success',
			'summary'           => __( 'Email verified.', 'astra' ),
			'body'              => __( 'Your business email has been confirmed.', 'astra' ),
			'email_label'       => __( 'Verified email:', 'astra' ),
			'primary_action'    => __( 'Continue access request', 'astra' ),
			'primary_href'      => home_url( '/auth/onboarding' ),
			'resend'            => false,
			'secondary_actions' => array(
				array( 'label' => __( 'Sign in with another account', 'astra' ), 'href' => home_url( '/auth/sign-in' ) ),
			),
			'footer'            => $support_link,
		),
		'expired' => array(
			'eyebrow'           => __( 'Link expired', 'astra' ),
			'title'             => __( 'Verification link expired', 'astra' ),
			'description'       => __( 'Request a new verification email to continue.', 'astra' ),
			'variant'           => 'warning',
			'summary'           => __( 'Verification link expired.', 'astra' ),
			'body'              => __( 'This link can no longer verify your email. Request a new verification email to continue.', 'astra' ),
			'email_label'       => __( 'Email address:', 'astra' ),
			'primary_action'    => __( 'Send a new verification email', 'astra' ),
			'primary_href'      => '',
			'resend'            => true,
			'secondary_actions' => array(
				array( 'label' => __( 'Use a different email', 'astra' ), 'href' => home_url( '/auth/signup' ) ),
				array( 'label' => __( 'Back to sign in', 'astra' ), 'href' => home_url( '/auth/sign-in' ) ),
			),
			'footer'            => $support_link,
		),
		'failed' => array(
			'eyebrow'           => __( 'Link unavailable', 'astra' ),
			'title'             => __( 'Verification link cannot be used', 'astra' ),
			'description'       => __( 'Request a new email if you still need to verify.', 'astra' ),
			'variant'           => 'error',
			'summary'           => __( 'Verification link cannot be used.', 'astra' ),
			'body'              => __( 'We could not verify your email with this link. Request a new email if you still need to verify.', 'astra' ),
			'email_label'       => __( 'Email address:', 'astra' ),
			'primary_action'    => __( 'Send verification email', 'astra' ),
			'primary_href'      => '',
			'resend'            => true,
			'secondary_actions' => array(
				array( 'label' => __( 'Back to sign in', 'astra' ), 'href' => home_url( '/auth/sign-in' ) ),
				array( 'label' => __( 'Contact support', 'astra' ), 'href' => 'mailto:support@abit.ai' ),
			),
			'footer'            => esc_html__( 'Token details are not shown for security.', 'astra' ),
		),
		'cooldown' => array(
			'eyebrow'           => __( 'Resend cooldown', 'astra' ),
			'title'             => __( 'Please wait before requesting another email', 'astra' ),
			'description'       => __( 'The resend request is rate limited. Try again after the cooldown.', 'astra' ),
			'variant'           => 'warning',
			'summary'           => __( 'Please wait before requesting another verification email.', 'astra' ),
			'body'              => __( 'We could not send another verification email yet. Check your inbox and spam folder, or try again later.', 'astra' ),
			'email_label'       => __( 'Verification email for:', 'astra' ),
			'primary_action'    => __( 'Back to sign in', 'astra' ),
			'primary_href'      => home_url( '/auth/sign-in' ),
			'resend'            => false,
			'secondary_actions' => array(
				array( 'label' => __( 'Use a different email', 'astra' ), 'href' => home_url( '/auth/signup' ) ),
				array( 'label' => __( 'Contact support', 'astra' ), 'href' => 'mailto:support@abit.ai' ),
			),
			'footer'            => $signin_link,
		),
		'already_verified' => array(
			'eyebrow'           => __( 'Already verified', 'astra' ),
			'title'             => __( 'Email already verified', 'astra' ),
			'description'       => __( 'This email address is already confirmed.', 'astra' ),
			'variant'           => 'success',
			'summary'           => __( 'Email already verified.', 'astra' ),
			'body'              => __( 'This email address is already confirmed. Continue to the next status-aware step for this request.', 'astra' ),
			'email_label'       => __( 'Verified email:', 'astra' ),
			'primary_action'    => __( 'Continue access request', 'astra' ),
			'primary_href'      => home_url( '/auth/onboarding' ),
			'resend'            => false,
			'secondary_actions' => array(
				array( 'label' => __( 'Back to sign in', 'astra' ), 'href' => home_url( '/auth/sign-in' ) ),
			),
			'footer'            => $support_link,
		),
	);

	return isset( $states[ $state ] ) ? $states[ $state ] : $states['required'];
}

function abitai_auth_render_reset_slot() {
	$state      = abitai_auth_get_reset_state();
	$copy       = abitai_auth_get_reset_state_copy( $state );
	$token      = abitai_auth_get_reset_token();
	$error      = isset( $_GET['reset_error'] ) ? sanitize_key( wp_unslash( $_GET['reset_error'] ) ) : '';
	$mock_state = isset( $_GET['mock_response'] ) ? sanitize_key( wp_unslash( $_GET['mock_response'] ) ) : '';

	if ( 'request' === $state ) :
	?>
	<?php if ( 'rate_limited' === $error || 'cooldown' === $error ) : ?>
		<div id="abit-auth-reset-request-status" class="abit-auth-alert abit-auth-alert--warning" role="alert" tabindex="-1" data-auth-autofocus>
			<strong><?php esc_html_e( 'Please wait before requesting another reset email.', 'astra' ); ?></strong>
		</div>
	<?php endif; ?>
	<form class="abit-auth-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-auth-reset-request-form novalidate>
		<input type="hidden" name="action" value="abitai_mock_password_reset_request" />
		<input type="hidden" name="mock_response" value="<?php echo esc_attr( $mock_state ); ?>" />
		<?php wp_nonce_field( 'abitai_mock_password_reset_request', 'abitai_reset_request_nonce' ); ?>
		<div class="abit-auth-field" data-auth-field="reset_email">
			<label for="abit-auth-reset-email"><?php esc_html_e( 'Email address', 'astra' ); ?></label>
			<input id="abit-auth-reset-email" class="abit-auth-input" type="email" name="email" autocomplete="email" inputmode="email" placeholder="<?php echo esc_attr__( 'jane@company.com', 'astra' ); ?>" aria-describedby="abit-auth-reset-email-error" required />
			<p id="abit-auth-reset-email-error" class="abit-auth-error" hidden><?php esc_html_e( 'Enter a valid email address.', 'astra' ); ?></p>
		</div>
		<div class="abit-auth-actions">
			<button type="submit" class="abit-auth-button abit-auth-button--primary" data-auth-submit data-loading-label="<?php echo esc_attr__( 'Sending...', 'astra' ); ?>"><?php esc_html_e( 'Send reset instructions', 'astra' ); ?></button>
		</div>
	</form>
	<p class="abit-auth-route-footer">
		<a href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>" data-auth-lockable><?php esc_html_e( 'Back to sign in', 'astra' ); ?></a>
	</p>
	<?php
		return;
	endif;

	if ( 'accepted' === $state || 'success' === $state || 'expired' === $state || 'invalid' === $state ) :
		?>
		<div id="abit-auth-reset-status" class="abit-auth-alert abit-auth-alert--<?php echo esc_attr( $copy['variant'] ); ?>" role="<?php echo esc_attr( 'invalid' === $state ? 'alert' : 'status' ); ?>" tabindex="-1" data-auth-autofocus>
			<strong><?php echo esc_html( $copy['summary'] ); ?></strong>
			<span><?php echo esc_html( $copy['body'] ); ?></span>
		</div>
		<div class="abit-auth-actions">
			<a class="abit-auth-button abit-auth-button--primary" href="<?php echo esc_url( $copy['primary_href'] ); ?>"><?php echo esc_html( $copy['primary_action'] ); ?></a>
			<?php foreach ( $copy['secondary_actions'] as $action ) : ?>
				<a class="abit-auth-button abit-auth-button--secondary" href="<?php echo esc_url( $action['href'] ); ?>"><?php echo esc_html( $action['label'] ); ?></a>
			<?php endforeach; ?>
		</div>
		<?php
		return;
	endif;

	if ( 'checking' === $state ) :
		?>
		<div id="abit-auth-reset-status" class="abit-auth-alert abit-auth-alert--info" role="status" tabindex="-1" data-auth-autofocus>
			<strong><?php esc_html_e( 'Checking reset link.', 'astra' ); ?></strong>
			<span><?php esc_html_e( 'Please wait while we check whether this reset link can be used.', 'astra' ); ?></span>
		</div>
		<div class="abit-auth-actions">
			<button type="button" class="abit-auth-button abit-auth-button--primary is-loading" aria-busy="true" disabled><?php esc_html_e( 'Validating...', 'astra' ); ?></button>
		</div>
		<script>
			window.setTimeout(function () {
				var url = new URL(window.location.href);
				url.searchParams.set('state', 'set');
				window.location.replace(url.toString());
			}, 350);
		</script>
		<?php
		return;
	endif;

	if ( 'set' === $state ) :
		?>
	<?php if ( 'mismatch' === $error || 'weak_password' === $error || 'missing_fields' === $error ) : ?>
		<div id="abit-auth-reset-submit-status" class="abit-auth-alert abit-auth-alert--error" role="alert" tabindex="-1" data-auth-autofocus>
			<strong><?php esc_html_e( 'We could not update your password.', 'astra' ); ?></strong>
			<span><?php echo esc_html( abitai_auth_get_reset_error_message( $error ) ); ?></span>
		</div>
	<?php endif; ?>
	<form class="abit-auth-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-auth-reset-password-form novalidate>
		<input type="hidden" name="action" value="abitai_mock_password_reset_submit" />
		<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>" />
		<?php wp_nonce_field( 'abitai_mock_password_reset_submit', 'abitai_reset_submit_nonce' ); ?>
		<div class="abit-auth-field" data-auth-field="reset_password">
			<label for="abit-auth-new-password"><?php esc_html_e( 'New password', 'astra' ); ?></label>
			<input id="abit-auth-new-password" class="abit-auth-input" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="128" aria-describedby="abit-auth-new-password-help abit-auth-new-password-error" required />
			<p id="abit-auth-new-password-help" class="abit-auth-help"><?php esc_html_e( 'Minimum 12 characters.', 'astra' ); ?></p>
			<p id="abit-auth-new-password-error" class="abit-auth-error" hidden><?php esc_html_e( 'Use at least 12 characters and avoid common passwords.', 'astra' ); ?></p>
		</div>
		<div class="abit-auth-field" data-auth-field="reset_confirm_password">
			<label for="abit-auth-confirm-new-password"><?php esc_html_e( 'Confirm new password', 'astra' ); ?></label>
			<input id="abit-auth-confirm-new-password" class="abit-auth-input" type="password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="128" aria-describedby="abit-auth-confirm-new-password-error" required />
			<p id="abit-auth-confirm-new-password-error" class="abit-auth-error" hidden><?php esc_html_e( 'Passwords do not match.', 'astra' ); ?></p>
		</div>
		<div class="abit-auth-actions">
			<button type="submit" class="abit-auth-button abit-auth-button--primary" data-auth-submit data-loading-label="<?php echo esc_attr__( 'Saving...', 'astra' ); ?>"><?php esc_html_e( 'Save new password', 'astra' ); ?></button>
			<a class="abit-auth-button abit-auth-button--secondary" href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>" data-auth-lockable><?php esc_html_e( 'Back to sign in', 'astra' ); ?></a>
		</div>
	</form>
		<?php
	endif;
}

function abitai_auth_get_reset_token() {
	$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

	return preg_replace( '/[^A-Za-z0-9_\-]/', '', $token );
}

function abitai_auth_get_reset_state() {
	$state = isset( $_GET['state'] ) ? sanitize_key( wp_unslash( $_GET['state'] ) ) : '';
	$token = abitai_auth_get_reset_token();

	$state_aliases = array(
		'request_accepted' => 'accepted',
		'check_email'      => 'accepted',
		'valid'            => 'set',
		'validating'       => 'checking',
		'cannot_use'       => 'invalid',
		'failed'           => 'invalid',
		'used'             => 'invalid',
		'expired_link'     => 'expired',
		'updated'          => 'success',
	);

	if ( isset( $state_aliases[ $state ] ) ) {
		$state = $state_aliases[ $state ];
	}

	if ( '' !== $token && function_exists( 'abitai_auth_password_reset_token_state' ) && in_array( $state, array( '', 'checking', 'set' ), true ) ) {
		$state = abitai_auth_password_reset_token_state( $token );
	}

	if ( '' === $state && '' !== $token ) {
		$token_states = array(
			'valid-reset-token'   => 'checking',
			'valid'               => 'checking',
			'expired-reset-token' => 'expired',
			'expired'             => 'expired',
			'used-reset-token'    => 'invalid',
			'invalid-reset-token' => 'invalid',
			'invalid'             => 'invalid',
		);

		$state = isset( $token_states[ $token ] ) ? $token_states[ $token ] : 'invalid';
	}

	$allowed_states = array( 'request', 'accepted', 'checking', 'set', 'expired', 'invalid', 'success' );

	return in_array( $state, $allowed_states, true ) ? $state : 'request';
}

function abitai_auth_get_reset_state_copy( $state ) {
	$states = array(
		'request'  => array(
			'eyebrow'           => __( 'Account recovery', 'astra' ),
			'title'             => __( 'Reset your password', 'astra' ),
			'description'       => __( 'Enter your email and we will send reset instructions if the account is eligible.', 'astra' ),
			'variant'           => 'info',
			'summary'           => __( 'Reset your password.', 'astra' ),
			'body'              => __( 'Enter your email and we will send reset instructions if the account is eligible.', 'astra' ),
			'primary_action'    => __( 'Send reset instructions', 'astra' ),
			'primary_href'      => '',
			'secondary_actions' => array(),
		),
		'accepted' => array(
			'eyebrow'           => __( 'Check email', 'astra' ),
			'title'             => __( 'Check your email', 'astra' ),
			'description'       => __( 'If an eligible account exists, reset instructions will be sent to that address.', 'astra' ),
			'variant'           => 'info',
			'summary'           => __( 'Check your email.', 'astra' ),
			'body'              => __( 'If an eligible account exists, reset instructions will be sent to that address.', 'astra' ),
			'primary_action'    => __( 'Back to sign in', 'astra' ),
			'primary_href'      => home_url( '/auth/sign-in' ),
			'secondary_actions' => array(
				array( 'label' => __( 'Send another reset request', 'astra' ), 'href' => home_url( '/auth/reset' ) ),
			),
		),
		'checking' => array(
			'eyebrow'           => __( 'Checking link', 'astra' ),
			'title'             => __( 'Checking reset link', 'astra' ),
			'description'       => __( 'Please wait while we check whether this reset link can be used.', 'astra' ),
			'variant'           => 'info',
			'summary'           => __( 'Checking reset link.', 'astra' ),
			'body'              => __( 'Please wait while we check whether this reset link can be used.', 'astra' ),
			'primary_action'    => __( 'Validating...', 'astra' ),
			'primary_href'      => '',
			'secondary_actions' => array(),
		),
		'set'      => array(
			'eyebrow'           => __( 'New password', 'astra' ),
			'title'             => __( 'Create a new password', 'astra' ),
			'description'       => __( 'Use a strong password for your abit.ai account.', 'astra' ),
			'variant'           => 'info',
			'summary'           => __( 'Create a new password.', 'astra' ),
			'body'              => __( 'Use a strong password for your abit.ai account.', 'astra' ),
			'primary_action'    => __( 'Save new password', 'astra' ),
			'primary_href'      => '',
			'secondary_actions' => array(),
		),
		'expired'  => array(
			'eyebrow'           => __( 'Link expired', 'astra' ),
			'title'             => __( 'Reset link expired', 'astra' ),
			'description'       => __( 'This link can no longer reset your password. Request a new reset email to continue.', 'astra' ),
			'variant'           => 'warning',
			'summary'           => __( 'Reset link expired.', 'astra' ),
			'body'              => __( 'This link can no longer reset your password. Request a new reset email to continue.', 'astra' ),
			'primary_action'    => __( 'Send a new reset email', 'astra' ),
			'primary_href'      => home_url( '/auth/reset' ),
			'secondary_actions' => array(
				array( 'label' => __( 'Back to sign in', 'astra' ), 'href' => home_url( '/auth/sign-in' ) ),
			),
		),
		'invalid'  => array(
			'eyebrow'           => __( 'Link unavailable', 'astra' ),
			'title'             => __( 'Reset link cannot be used', 'astra' ),
			'description'       => __( 'We could not reset your password with this link. Request a new reset email if you still need to change your password.', 'astra' ),
			'variant'           => 'error',
			'summary'           => __( 'Reset link cannot be used.', 'astra' ),
			'body'              => __( 'We could not reset your password with this link. Request a new reset email if you still need to change your password.', 'astra' ),
			'primary_action'    => __( 'Send reset email', 'astra' ),
			'primary_href'      => home_url( '/auth/reset' ),
			'secondary_actions' => array(
				array( 'label' => __( 'Back to sign in', 'astra' ), 'href' => home_url( '/auth/sign-in' ) ),
				array( 'label' => __( 'Contact support', 'astra' ), 'href' => 'mailto:support@abit.ai' ),
			),
		),
		'success'  => array(
			'eyebrow'           => __( 'Password updated', 'astra' ),
			'title'             => __( 'Password updated', 'astra' ),
			'description'       => __( 'Your password has been changed. Sign in with your new password to continue.', 'astra' ),
			'variant'           => 'success',
			'summary'           => __( 'Password updated.', 'astra' ),
			'body'              => __( 'Your password has been changed. Sign in with your new password to continue.', 'astra' ),
			'primary_action'    => __( 'Back to sign in', 'astra' ),
			'primary_href'      => home_url( '/auth/sign-in' ),
			'secondary_actions' => array(),
		),
	);

	return isset( $states[ $state ] ) ? $states[ $state ] : $states['request'];
}

function abitai_auth_get_reset_error_message( $error ) {
	$messages = array(
		'missing_fields' => __( 'Complete this field to continue.', 'astra' ),
		'weak_password'  => __( 'Use at least 12 characters and avoid common passwords.', 'astra' ),
		'mismatch'       => __( 'Passwords do not match.', 'astra' ),
	);

	return isset( $messages[ $error ] ) ? $messages[ $error ] : __( 'We could not reset your password with this link. Request a new reset email if you still need to change your password.', 'astra' );
}
