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
					case 'app':
						abitai_auth_render_status_slot( $route_key );
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
	<div class="abit-auth-stepper" aria-label="<?php echo esc_attr__( 'Signup progress', 'astra' ); ?>">
		<div class="abit-auth-stepper__item is-active" aria-current="step" data-auth-stepper-item="account"><span class="abit-auth-stepper__marker">1</span><span><?php esc_html_e( 'Account', 'astra' ); ?></span></div>
		<div class="abit-auth-stepper__item" data-auth-stepper-item="company"><span class="abit-auth-stepper__marker">2</span><span><?php esc_html_e( 'Company', 'astra' ); ?></span></div>
		<div class="abit-auth-stepper__item" data-auth-stepper-item="modules"><span class="abit-auth-stepper__marker">3</span><span><?php esc_html_e( 'ERP needs', 'astra' ); ?></span></div>
	</div>
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
			<fieldset class="abit-auth-module-selector" data-auth-field="erp_module_interest" aria-describedby="abit-auth-module-interest-error">
				<legend><?php esc_html_e( 'ERP module interest', 'astra' ); ?></legend>
				<p id="abit-auth-module-interest-error" class="abit-auth-error" hidden><?php esc_html_e( 'Select at least one ERP module interest.', 'astra' ); ?></p>
				<div class="abit-auth-module-grid">
					<?php foreach ( $module_options as $value => $label ) : ?>
						<label class="abit-auth-module-option<?php echo in_array( $value, $selected_modules, true ) ? ' is-selected' : ''; ?>">
							<input type="checkbox" name="erp_module_interest[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $selected_modules, true ) ); ?> />
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
	$email_value = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
	$email_label = '' !== $email_value ? $email_value : __( 'your business email', 'astra' );
	?>
	<div class="abit-auth-alert abit-auth-alert--info" role="status">
		<strong><?php esc_html_e( 'Verification required.', 'astra' ); ?></strong>
		<span><?php esc_html_e( 'Verify your business email before continuing to product access.', 'astra' ); ?></span>
	</div>
	<div class="abit-auth-status-box">
		<span><?php esc_html_e( 'Verification email sent to:', 'astra' ); ?></span>
		<strong><?php echo esc_html( $email_label ); ?></strong>
	</div>
	<div class="abit-auth-actions">
		<a class="abit-auth-button abit-auth-button--primary" href="mailto:"><?php esc_html_e( 'Open email app', 'astra' ); ?></a>
		<a class="abit-auth-button abit-auth-button--secondary" href="<?php echo esc_url( home_url( '/auth/signup' ) ); ?>"><?php esc_html_e( 'Change email', 'astra' ); ?></a>
	</div>
	<p class="abit-auth-route-footer">
		<a href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>"><?php esc_html_e( 'Back to sign in', 'astra' ); ?></a>
		<span aria-hidden="true">|</span>
		<a href="mailto:support@abit.ai"><?php esc_html_e( 'Contact support', 'astra' ); ?></a>
	</p>
	<?php
}

function abitai_auth_render_reset_slot() {
	?>
	<form class="abit-auth-form" action="<?php echo esc_url( wp_lostpassword_url() ); ?>" method="post">
		<div class="abit-auth-field">
			<label for="abit-auth-reset-email"><?php esc_html_e( 'Email address', 'astra' ); ?></label>
			<input id="abit-auth-reset-email" class="abit-auth-input" type="email" name="user_login" autocomplete="email" required />
			<p class="abit-auth-help"><?php esc_html_e( 'If an eligible account exists, reset instructions will be sent to that address.', 'astra' ); ?></p>
		</div>
		<div class="abit-auth-actions">
			<button type="submit" class="abit-auth-button abit-auth-button--primary"><?php esc_html_e( 'Send reset instructions', 'astra' ); ?></button>
		</div>
	</form>
	<p class="abit-auth-route-footer">
		<a href="<?php echo esc_url( home_url( '/auth/sign-in' ) ); ?>"><?php esc_html_e( 'Back to sign in', 'astra' ); ?></a>
	</p>
	<?php
}
